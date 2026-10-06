<?php

namespace WPKirk\WPBones\Database\Migrations;

/**
 * Where the Migrator keeps its state for one plugin on one site: the version the database was
 * migrated for (the cheap check every request makes), the migrations that ran, the last failure
 * and the lock that lets one request at a time run them.
 *
 * OptionRepository keeps it in WordPress options. A table could do the same behind this interface.
 *
 * @since 3.0.0
 */
interface MigrationRepository
{
  /**
   * The plugin version the database was last migrated for, null on a site that never ran the
   * Migrator. Read on every request, so it must be cheap: an autoloaded option.
   */
  public function version(): ?string;

  /**
   * The failure that stopped the last run, null when it completed.
   *
   * @return array{migration: string, message: string, version: string, time: int}|null
   */
  public function failure(): ?array;

  /**
   * Take the lock. False when another request holds a lock that has not expired.
   */
  public function lock(): bool;

  public function unlock(): void;

  /**
   * Read the state again from the database, past every cache: what another request wrote while
   * this one waited for the lock must be seen. Called right after lock().
   */
  public function refresh(): void;

  /**
   * The migrations that ran, by name.
   *
   * @return array<string, array{batch: int, version: string, time: int}>
   */
  public function ran(): array;

  public function log(string $migration, int $batch, string $version): void;

  public function setVersion(string $version): void;

  /**
   * @param array{migration: string, message: string, version: string, time: int}|null $failure
   */
  public function setFailure(?array $failure): void;
}
