<?php

namespace WPKirk\WPBones\Database\Migrations;

/**
 * The Migrator's state in four WordPress options of the current site, named after the plugin:
 *
 * - `{prefix}_db_version`         the plugin version the database was migrated for, autoloaded,
 *                                 so the check every request makes costs no query;
 * - `{prefix}_migrations`         the migrations that ran: name → batch, plugin version, time;
 * - `{prefix}_migrations_failure` the failure that stopped the last run, if any;
 * - `{prefix}_migrations.lock`    the lock, held while a request runs migrations.
 *
 * Options are per site, so on a multisite network every site migrates its own tables, the first
 * time one of its pages loads.
 *
 * @since 3.0.0
 */
class OptionRepository implements MigrationRepository
{
  /**
   * After this many seconds a lock is taken as abandoned (its request died) and can be taken over.
   * WooCommerce's installer uses the same.
   */
  public const LOCK_TIMEOUT = 600;

  protected string $versionOption;

  protected string $ledgerOption;

  protected string $failureOption;

  protected string $lockOption;

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

    $inserted = $wpdb->query(
      $wpdb->prepare(
        "INSERT IGNORE INTO `{$wpdb->options}` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, 'off') /* LOCK */",
        $this->lockOption,
        (string) time()
      )
    );

    if ($inserted) {
      return true;
    }

    $since = (int) $this->read($this->lockOption);

    if ($since > time() - static::LOCK_TIMEOUT) {
      return false;
    }

    // An abandoned lock: take it over, unless another request just did.
    return (bool) $wpdb->query(
      $wpdb->prepare(
        "UPDATE `{$wpdb->options}` SET `option_value` = %s WHERE `option_name` = %s AND `option_value` = %s",
        (string) time(),
        $this->lockOption,
        (string) $since
      )
    );
  }

  public function unlock(): void
  {
    global $wpdb;

    $wpdb->query($wpdb->prepare("DELETE FROM `{$wpdb->options}` WHERE `option_name` = %s", $this->lockOption));
  }

  public function refresh(): void
  {
    $version = $this->read($this->versionOption);
    $ran = $this->read($this->ledgerOption);
    $failure = $this->read($this->failureOption);

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

  public function log(string $migration, int $batch, string $version): void
  {
    $ran = $this->ran();
    $ran[$migration] = ['batch' => $batch, 'version' => $version, 'time' => time()];

    update_option($this->ledgerOption, $ran, false);

    if ($this->fresh !== null) {
      $this->fresh['ran'] = $ran;
    }
  }

  public function setVersion(string $version): void
  {
    update_option($this->versionOption, $version, true);

    if ($this->fresh !== null) {
      $this->fresh['version'] = $version;
    }
  }

  public function setFailure(?array $failure): void
  {
    if ($failure === null) {
      delete_option($this->failureOption);
    } else {
      update_option($this->failureOption, $failure, true);
    }

    if ($this->fresh !== null) {
      $this->fresh['failure'] = $failure;
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
