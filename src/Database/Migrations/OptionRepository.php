<?php

namespace WPKirk\WPBones\Database\Migrations;

/**
 * The Migrator's state in four WordPress options of the current site, named after the plugin:
 *
 * - `{prefix}_db_version`         the plugin version the database was migrated for, autoloaded,
 *                                 so the check every request makes costs no query;
 * - `{prefix}_migrations`         the migrations that ran: name → batch, plugin version, time;
 * - `{prefix}_migrations_failure` the failure that stopped the last run, or '' once one completes;
 *                                 autoloaded and always present, so reading it costs no query;
 * - `{prefix}_migrations.lock`    the lock, `time|token`, held while a request runs migrations.
 *
 * Options are per site, so on a multisite network every site migrates its own tables, the first
 * time one of its pages loads.
 *
 * @since 3.0.0
 */
class OptionRepository implements MigrationRepository
{
  /**
   * After this many seconds without a touch() a lock is taken as abandoned (its request died) and
   * can be taken over: an hour, WP_Upgrader::create_lock()'s default. touch() runs before each
   * migration, so only a single migration longer than that could be overtaken.
   */
  public const LOCK_TIMEOUT = 3600;

  protected string $versionOption;

  protected string $ledgerOption;

  protected string $failureOption;

  protected string $lockOption;

  /**
   * The value of the lock row this request inserted, `time|token`; null when it holds none.
   */
  protected ?string $held = null;

  /**
   * What refresh() read, past the caches; null until it runs.
   *
   * @var array{version: ?string, ran: array, failure: ?array}|null
   */
  protected ?array $fresh = null;

  /**
   * @param string $prefix The plugin's slug.
   */
  public function __construct(string $prefix)
  {
    $this->versionOption = "{$prefix}_db_version";
    $this->ledgerOption = "{$prefix}_migrations";
    $this->failureOption = "{$prefix}_migrations_failure";
    $this->lockOption = "{$prefix}_migrations.lock";
  }

  public function version(): ?string
  {
    $version = $this->fresh === null ? get_option($this->versionOption, null) : $this->fresh['version'];

    return is_string($version) && $version !== '' ? $version : null;
  }

  public function failure(): ?array
  {
    $failure = $this->fresh === null ? get_option($this->failureOption, null) : $this->fresh['failure'];

    return is_array($failure) ? $failure : null;
  }

  /**
   * The lock is an options row inserted with INSERT IGNORE, as WP_Upgrader::create_lock() and
   * WooCommerce do: of two requests racing for it, the database lets exactly one insert the row.
   * add_option() would not do: it reads first and then upserts, so both could win.
   */
  public function lock(): bool
  {
    global $wpdb;

    $value = $this->lockValue();

    $inserted = $wpdb->query(
      $wpdb->prepare(
        "INSERT IGNORE INTO `{$wpdb->options}` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, 'off') /* LOCK */",
        $this->lockOption,
        $value
      )
    );

    if ($inserted) {
      $this->held = $value;

      return true;
    }

    $current = (string) $this->read($this->lockOption);

    if ((int) $current > time() - static::LOCK_TIMEOUT) {
      return false;
    }

    // An abandoned lock: take it over, unless another request just did.
    $taken = $wpdb->query(
      $wpdb->prepare(
        "UPDATE `{$wpdb->options}` SET `option_value` = %s WHERE `option_name` = %s AND `option_value` = %s",
        $value,
        $this->lockOption,
        $current
      )
    );

    if (!$taken) {
      return false;
    }

    $this->held = $value;

    return true;
  }

  public function touch(): void
  {
    global $wpdb;

    if ($this->held === null) {
      return;
    }

    $value = $this->lockValue();

    $touched = $wpdb->query(
      $wpdb->prepare(
        "UPDATE `{$wpdb->options}` SET `option_value` = %s WHERE `option_name` = %s AND `option_value` = %s",
        $value,
        $this->lockOption,
        $this->held
      )
    );

    // No row: another request took the lock over after a timeout. unlock() then leaves its row
    // alone, since the value no longer matches.
    if ($touched) {
      $this->held = $value;
    }
  }

  /**
   * Delete the lock row only while it still carries this request's value: one taken over after a
   * timeout belongs to another request now.
   */
  public function unlock(): void
  {
    global $wpdb;

    if ($this->held === null) {
      return;
    }

    $wpdb->query(
      $wpdb->prepare(
        "DELETE FROM `{$wpdb->options}` WHERE `option_name` = %s AND `option_value` = %s",
        $this->lockOption,
        $this->held
      )
    );

    $this->held = null;
  }

  /**
   * `time|token`: the time says how old the lock is, the token whose it is.
   */
  protected function lockValue(): string
  {
    return time() . '|' . bin2hex(random_bytes(8));
  }

  public function refresh(): void
  {
    $version = $this->read($this->versionOption);
    $ran = $this->read($this->ledgerOption);
    $failure = $this->read($this->failureOption);

    // A persistent object cache can keep a version older than the database (after an import, a
    // restore): every request would find the migrations due, wait for the lock, and see here
    // that they are not. Drop the cached copies so the next request reads what is true.
    if (get_option($this->versionOption, null) !== $version || get_option($this->failureOption, null) !== $failure) {
      wp_cache_delete('alloptions', 'options');
      wp_cache_delete($this->versionOption, 'options');
      wp_cache_delete($this->failureOption, 'options');
    }

    $this->fresh = [
      'version' => is_string($version) && $version !== '' ? $version : null,
      'ran' => is_array($ran) ? $ran : [],
      'failure' => is_array($failure) ? $failure : null,
    ];
  }

  public function ran(): array
  {
    if ($this->fresh !== null) {
      return $this->fresh['ran'];
    }

    $ran = $this->read($this->ledgerOption);

    return is_array($ran) ? $ran : [];
  }

  public function log(string $migration, int $batch, string $version): bool
  {
    $ran = $this->ran();
    $ran[$migration] = ['batch' => $batch, 'version' => $version, 'time' => time()];

    // update_option() answers false also for a value that did not change; the entry is new, so a
    // false is checked against what the database holds.
    if (!update_option($this->ledgerOption, $ran, false)) {
      $stored = $this->read($this->ledgerOption);

      if (!is_array($stored) || !isset($stored[$migration])) {
        return false;
      }
    }

    if ($this->fresh !== null) {
      $this->fresh['ran'] = $ran;
    }

    return true;
  }

  public function setVersion(string $version): bool
  {
    // As in log(): a false is checked against what the database holds.
    if (!update_option($this->versionOption, $version, true) && $this->read($this->versionOption) !== $version) {
      return false;
    }

    if ($this->fresh !== null) {
      $this->fresh['version'] = $version;
    }

    return true;
  }

  public function setFailure(?array $failure): void
  {
    // An empty string rather than no option: an autoloaded option is read for free, a missing one
    // costs a query, and the failure is checked on every request.
    update_option($this->failureOption, $failure ?? '', true);

    if ($this->fresh !== null) {
      $this->fresh['failure'] = $failure;
    }
  }

  /**
   * Forget which migrations ran on this site, for an uninstall.php that drops the plugin's tables:
   * otherwise a reinstall finds them all recorded and creates nothing. Call it with the plugin's
   * slug, the name of its options row (`WPKirk()->slug`, or `<plugin_name>_slug`).
   */
  public static function forget(string $prefix): void
  {
    foreach (['_db_version', '_migrations', '_migrations_failure', '_migrations.lock'] as $suffix) {
      delete_option($prefix . $suffix);
    }
  }

  /**
   * An option's value straight from the database: get_option() can answer from a cache that
   * predates what another request wrote.
   *
   * @return mixed The unserialized value, null when the option does not exist.
   */
  protected function read(string $name)
  {
    global $wpdb;

    $value = $wpdb->get_var(
      $wpdb->prepare("SELECT `option_value` FROM `{$wpdb->options}` WHERE `option_name` = %s LIMIT 1", $name)
    );

    return $value === null ? null : maybe_unserialize($value);
  }
}
