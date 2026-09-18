<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Support;

/**
 * A stand-in for the global $wpdb that records every SQL string it receives and
 * returns empty results. It lets the unit suite assert on the SQL the framework
 * *builds* without a database.
 *
 * Only the members the framework touches are implemented; add more when a test needs
 * them, never speculatively.
 */
final class WpdbSpy
{
  public string $prefix = 'wp_';

  public int $insert_id = 0;

  /** @var string[] every statement, in order */
  public array $queries = [];

  public function get_results(string $sql, mixed $output = null): array
  {
    $this->queries[] = $sql;

    return [];
  }

  public function get_var(string $sql): mixed
  {
    $this->queries[] = $sql;

    return 0;
  }

  public function get_row(string $sql): mixed
  {
    $this->queries[] = $sql;

    return null;
  }

  public function query(string $sql): int
  {
    $this->queries[] = $sql;

    return 0;
  }

  /** Mirrors wpdb::_real_escape closely enough for assertions on quoting. */
  public function _real_escape(string $value): string
  {
    return addslashes($value);
  }

  public function reset(): void
  {
    $this->queries = [];
  }

  public function last(): string
  {
    return $this->queries === [] ? '' : end($this->queries);
  }
}
