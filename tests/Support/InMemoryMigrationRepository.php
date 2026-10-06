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

  public function unlock(): void
  {
    $this->calls[] = 'unlock';
    $this->locked = false;
  }

  public function refresh(): void
  {
    $this->calls[] = 'refresh';
  }

  public function ran(): array
  {
    return $this->ran;
  }

  public function log(string $migration, int $batch, string $version): void
  {
    $this->calls[] = "log {$migration}";
    $this->ran[$migration] = ['batch' => $batch, 'version' => $version, 'time' => time()];
  }

  public function setVersion(string $version): void
  {
    $this->calls[] = "version {$version}";
    $this->version = $version;
  }

  public function setFailure(?array $failure): void
  {
    $this->calls[] = $failure === null ? 'failure cleared' : "failure {$failure['migration']}";
    $this->failure = $failure;
  }
}
