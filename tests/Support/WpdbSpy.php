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
 *
 * Not modelled: wpdb::_real_escape() replaces `%` with a placeholder token that
 * wpdb::query() strips again through the `query` filter, so a LIKE pattern round-trips
 * in production. Here `%` is left alone; assertions on LIKE values check quoting only.
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

  /**
   * Mirrors wpdb::_real_escape(): non-scalars become '', scalars are escaped.
   *
   * @param mixed $value
   */
  public function _real_escape($value): string
  {
    return is_scalar($value) ? addslashes((string) $value) : '';
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
