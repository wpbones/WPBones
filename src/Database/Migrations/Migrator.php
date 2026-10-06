<?php

namespace WPKirk\WPBones\Database\Migrations;

use Throwable;

/**
 * Runs a plugin's migrations: each file in database/migrations/ once per site, in file name order.
 *
 * Which ones ran is recorded by file name, so a migration does not belong to a plugin version: the
 * stored version is only the cheap signal that something may have changed (isDue()), and the
 * decision is "files on disk minus names already recorded". A migration added during development,
 * or backported to an older branch, still runs exactly once wherever it is missing.
 *
 * The file name gives the order: `php bones migrate:create` prefixes a `Y_m_d_His` timestamp, so
 * name order is creation order, as in Laravel. A pending migration whose name sorts before one that
 * already ran (two branches merged out of order) still runs, with a warning.
 *
 * Replaces the 2.x flow, which ran every migration and every seeder on each activation and on each
 * update through upgrader_post_install (wpbones/WPBones#40).
 *
 * @since 3.0.0
 */
class Migrator
{
  /**
   * A failure is retried from the admin at most this often, so that a broken migration does not
   * run on every admin page load.
   */
  public const RETRY_AFTER = 60;

  /**
   * @param string              $path       The plugin's database/migrations folder.
   * @param string              $version    The plugin's version, from its header.
   * @param MigrationRepository $repository Where the state is kept.
   * @param string              $name       The plugin's name, for messages.
   */
  public function __construct(
    protected string $path,
    protected string $version,
    protected MigrationRepository $repository,
    protected string $name = ''
  ) {
  }

  /**
   * Whether this request should run the migrations: the database was migrated for another version
   * of the plugin, or never. Two autoloaded options, so every request can ask for free.
   *
   * After a failure of this version only an administrator's request retries, and not more than
   * every RETRY_AFTER seconds: a public page would retry on every hit. That holds whatever the
   * stored version says: a migration added without a version bump can fail too, during
   * development. A failure of another version does not hold back this one, which may fix it.
   */
  public function isDue(bool $byAdministrator = false): bool
  {
    $failure = $this->repository->failure();

    if ($failure !== null && ($failure['version'] ?? null) === $this->version) {
      return $byAdministrator && time() - (int) ($failure['time'] ?? 0) >= static::RETRY_AFTER;
    }

    return $failure !== null || $this->versionChanged();
  }

  /**
   * Whether the database was migrated for another version of the plugin than this one, or never.
   * Costs no query: the stored version is an autoloaded option.
   */
  public function versionChanged(): bool
  {
    return $this->repository->version() !== $this->version;
  }

  /**
   * The migration files, name => path, in the order they run.
   *
   * @return array<string, string>
   */
  public function files(): array
  {
    $files = [];

    foreach (glob(rtrim($this->path, '/') . '/*.php') ?: [] as $file) {
      $files[basename($file, '.php')] = $file;
    }

    // glob() sorts already; this keeps the order when a platform's glob does not.
    ksort($files, SORT_STRING);

    return $files;
  }

  /**
   * The migrations that have not run yet, name => path, in the order they will run.
   *
   * @return array<string, string>
   */
  public function pending(): array
  {
    return array_diff_key($this->files(), $this->repository->ran());
  }

  /**
   * Pending migrations whose name sorts before one that already ran. On a fresh site they run
   * earlier than on this one, so if one depends on the other the two sites end up different.
   *
   * @return string[]
   */
  public function outOfOrder(): array
  {
    $ran = array_keys($this->repository->ran());

    if ($ran === []) {
      return [];
    }

    $last = max($ran);

    return array_values(array_filter(array_keys($this->pending()), fn($name) => strcmp($name, $last) < 0));
  }

  /**
   * Every migration with its state, for `php bones migrate:status`.
   *
   * @return array<string, array{ran: bool, batch: ?int, version: ?string, time: ?int, outOfOrder: bool}>
   */
  public function status(): array
  {
    $ran = $this->repository->ran();
    $outOfOrder = array_flip($this->outOfOrder());
    $status = [];

    foreach (array_keys($this->files()) as $name) {
      $status[$name] = [
        'ran' => isset($ran[$name]),
        'batch' => $ran[$name]['batch'] ?? null,
        'version' => $ran[$name]['version'] ?? null,
        'time' => $ran[$name]['time'] ?? null,
        'outOfOrder' => isset($outOfOrder[$name]),
      ];
    }

    return $status;
  }

  /**
   * The failure that stopped the last run, if it has not been fixed since.
   *
   * @return array{migration: string, message: string, version: string, time: int}|null
   */
  public function failure(): ?array
  {
    return $this->repository->failure();
  }

  /**
   * Run every pending migration, in order, under the lock.
   *
   * Stops at the first one that throws or leaves a database error: it is not recorded, the failure
   * is, and the stored version stays where it was, so the next run starts from that migration.
   * When everything ran, the stored version becomes the plugin's.
   *
   * @param bool $automatic       True for the run a page load starts after isDue(): under the lock
   *                              it asks again, since another request may have finished, or
   *                              failed, in the meantime. Activation and `php bones migrate` run
   *                              whatever the state.
   * @param bool          $byAdministrator What isDue() was asked with.
   * @param callable|null $finish          The update's own work, called with the previous version
   *                                       (null when unknown) once the migrations ran and before
   *                                       the version moves, still under the lock: when it throws,
   *                                       the failure is recorded and the next run finishes the
   *                                       update again.
   */
  public function migrate(bool $automatic = false, bool $byAdministrator = false, ?callable $finish = null): MigrationResult
  {
    if (!$this->repository->lock()) {
      return new MigrationResult(null, true);
    }

    try {
      // Another request may have migrated while this one waited: read what it wrote.
      $this->repository->refresh();

      $result = new MigrationResult($this->repository->version());

      if ($automatic && !$this->isDue($byAdministrator)) {
        return $result;
      }

      $result->outOfOrder = $this->outOfOrder();

      foreach ($result->outOfOrder as $name) {
        $this->log("migration {$name} runs after migrations whose names sort later: check that it does not depend on them");
      }

      $batches = array_column($this->repository->ran(), 'batch');
      $batch = ($batches === [] ? 0 : max($batches)) + 1;

      foreach ($this->pending() as $name => $file) {
        $this->repository->touch();

        $error = $this->runFile($file);

        if ($error !== null) {
          $result->failed = $name;
          $result->error = $error;

          $this->repository->setFailure([
            'migration' => $name,
            'message' => $error,
            'version' => $this->version,
            'time' => time(),
          ]);

          $this->log("migration {$name} failed, and the ones after it did not run: {$error}");

          return $result;
        }

        $result->ran[] = $name;

        if (!$this->repository->log($name, $batch, $this->version)) {
          $result->failed = $name;
          $result->error = 'it ran, but could not be recorded, so it would run again: the database did not accept the write';

          $this->repository->setFailure([
            'migration' => $name,
            'message' => $result->error,
            'version' => $this->version,
            'time' => time(),
          ]);

          $this->log("migration {$name} {$result->error}");

          return $result;
        }
      }

      if ($finish !== null && $this->repository->version() !== $this->version) {
        try {
          $finish($result->previous);
        } catch (Throwable $e) {
          $result->failed = 'plugin/updated.php';
          $result->error = get_class($e) . ': ' . $e->getMessage();

          $this->repository->setFailure([
            'migration' => $result->failed,
            'message' => $result->error,
            'version' => $this->version,
            'time' => time(),
          ]);

          $this->log("the migrations ran, but finishing the update failed and will run again: {$result->error}");

          return $result;
        }
      }

      // Also on a site that never failed: the empty value keeps the option autoloaded.
      $this->repository->setFailure(null);

      // The version moves last: until it does, the next request finishes the update again.
      if ($this->repository->version() !== $this->version) {
        if ($this->repository->setVersion($this->version)) {
          $result->advanced = true;
        } else {
          $this->log("the version {$this->version} could not be stored: the next request tries again");
        }
      }

      return $result;
    } finally {
      $this->repository->unlock();
    }
  }

  /**
   * Include one migration file and run it.
   *
   * @return string|null Why it failed, or null.
   */
  protected function runFile(string $file): ?string
  {
    // WordPress records every database error here, including the ones it does not print.
    $errors = is_array($GLOBALS['EZSQL_ERROR'] ?? null) ? count($GLOBALS['EZSQL_ERROR']) : 0;

    try {
      // A static closure: the file sees neither $this nor this method's variables.
      $migration = (static fn() => include $file)();

      if (!is_object($migration) || !method_exists($migration, 'up')) {
        return 'the file does not return a migration (return new class extends Migration { ... };)';
      }

      $migration->up();
    } catch (Throwable $e) {
      return get_class($e) . ': ' . $e->getMessage();
    }

    // What Migration::create() already judged: the table and its columns are there.
    $tolerated = method_exists($migration, 'toleratedErrors') ? $migration->toleratedErrors() : [];

    foreach (array_slice($GLOBALS['EZSQL_ERROR'] ?? [], $errors) as $error) {
      $index = array_search($error, $tolerated, true);

      if ($index !== false) {
        unset($tolerated[$index]);

        continue;
      }

      // dbDelta() describes each table before creating it, so a new table always leaves a
      // "doesn't exist" error behind on the way to being created, also when a migration calls
      // dbDelta() itself.
      if (stripos(ltrim((string) ($error['query'] ?? '')), 'DESCRIBE ') === 0) {
        continue;
      }

      return 'database error: ' . ($error['error_str'] ?? 'unknown');
    }

    return null;
  }

  protected function log(string $message): void
  {
    error_log(sprintf('[WP Bones] %s: %s', $this->name !== '' ? $this->name : basename(dirname($this->path, 2)), $message));
  }
}
