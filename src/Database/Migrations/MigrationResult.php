<?php

namespace WPKirk\WPBones\Database\Migrations;

/**
 * What one Migrator::migrate() did.
 *
 * @since 3.0.0
 */
final class MigrationResult
{
  /**
   * The migrations that ran, in order.
   *
   * @var string[]
   */
  public array $ran = [];

  /**
   * Migrations that ran after one with a later name: see Migrator::outOfOrder().
   *
   * @var string[]
   */
  public array $outOfOrder = [];

  /**
   * The migration that failed, and why. The run stopped there.
   */
  public ?string $failed = null;

  public ?string $error = null;

  /**
   * True when this request stored the plugin's version, which it does only once the migrations and
   * the update's own work (Migrator::migrate()'s $finish) went through.
   */
  public bool $advanced = false;

  /**
   * @param string|null $previous The version the database was migrated for before this run.
   * @param bool        $locked   True when another request held the lock and nothing ran.
   */
  public function __construct(public readonly ?string $previous = null, public readonly bool $locked = false)
  {
  }

  public function ok(): bool
  {
    return !$this->locked && $this->failed === null;
  }
}
