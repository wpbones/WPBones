<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Support;

use WPKirk\WPBones\Database\Migrations\MigrationRepository;

/**
 * The Migrator's state in memory, with a lock that a test can hold from "another request".
 * OptionRepository, the WordPress-backed one, is exercised live by migrations-live-smoke.sh.
 */
final class InMemoryMigrationRepository implements MigrationRepository
{
  public ?string $version = null;

  /** @var array<string, array{batch: int, version: string, time: int}> */
  public array $ran = [];

  public ?array $failure = null;

  /** True while "another request" holds the lock. */
  public bool $heldElsewhere = false;

  /** True when the database refuses the ledger write. */
  public bool $logFails = false;

  /** True when the database refuses the version write. */
  public bool $versionFails = false;

  /**
   * What "another request" writes while this one waits for the lock: applied by refresh().
   *
   * @var array{version?: ?string, failure?: ?array}
   */
  public array $writtenMeanwhile = [];

  public bool $locked = false;

  /** @var string[] the calls that matter for ordering, in order */
  public array $calls = [];

  public function version(): ?string
  {
    return $this->version;
  }

  public function failure(): ?array
  {
    return $this->failure;
  }

  public function lock(): bool
  {
    $this->calls[] = 'lock';

    if ($this->heldElsewhere || $this->locked) {
      return false;
    }

    return $this->locked = true;
  }

  public function touch(): void
  {
    $this->calls[] = 'touch';
  }

  public function unlock(): void
  {
    $this->calls[] = 'unlock';
    $this->locked = false;
  }

  public function refresh(): void
  {
    $this->calls[] = 'refresh';

    foreach ($this->writtenMeanwhile as $property => $value) {
      $this->{$property} = $value;
    }
  }

  public function ran(): array
  {
    return $this->ran;
  }

  public function log(string $migration, int $batch, string $version): bool
  {
    $this->calls[] = "log {$migration}";

    if ($this->logFails) {
      return false;
    }

    $this->ran[$migration] = ['batch' => $batch, 'version' => $version, 'time' => time()];

    return true;
  }

  public function setVersion(string $version): bool
  {
    $this->calls[] = "version {$version}";

    if ($this->versionFails) {
      return false;
    }

    $this->version = $version;

    return true;
  }

  public function setFailure(?array $failure): void
  {
    $this->calls[] = $failure === null ? 'failure cleared' : "failure {$failure['migration']}";
    $this->failure = $failure;
  }
}
