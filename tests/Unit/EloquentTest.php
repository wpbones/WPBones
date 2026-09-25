<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Database\Eloquent;

/**
 * Eloquent::connection() reads WordPress's database constants, and a process can define a
 * constant only once, so every test runs in a process of its own.
 *
 * $wpdb is a stand-in that answers parse_db_host() the way WordPress does for the DB_HOST under
 * test (its real parser is exercised live by eloquent-live-smoke.sh): these tests are about how
 * that answer becomes Laravel's connection.
 */
final class EloquentTest extends TestCase
{
  private function defineMysqlConstants(string $host = 'db.example'): void
  {
    define('DB_HOST', $host);
    define('DB_NAME', 'wordpress');
    define('DB_USER', 'wp');
    define('DB_PASSWORD', 'secret');
  }

  /**
   * @param array|false $parsed What $wpdb->parse_db_host() returns.
   */
  private function wordpress(array|false $parsed, string $charset = 'utf8mb4', string $collate = 'utf8mb4_unicode_520_ci'): object
  {
    return $GLOBALS['wpdb'] = new class($parsed, $charset, $collate) {
      public ?string $parsedHost = null;

      public function __construct(private array|false $parsed, public string $charset, public string $collate)
      {
      }

      public function parse_db_host($host)
      {
        $this->parsedHost = $host;

        return $this->parsed;
      }
    };
  }

  private function mysql(array $overrides = []): array
  {
    return array_merge(
      [
        'driver' => 'mysql',
        'host' => 'db.example',
        'database' => 'wordpress',
        'username' => 'wp',
        'password' => 'secret',
        'charset' => 'utf8mb4',
        'prefix' => '',
        'collation' => 'utf8mb4_unicode_520_ci',
      ],
      $overrides
    );
  }

  #[RunInSeparateProcess]
  #[PreserveGlobalState(false)]
  public function test_mysql_uses_the_charset_and_collation_wordpress_settled_on(): void
  {
    // Measured on wpbones.test: DB_CHARSET is 'utf8', and WordPress raised it to utf8mb4.
    define('DB_CHARSET', 'utf8');
    $this->defineMysqlConstants();
    $wpdb = $this->wordpress(['db.example', null, null, false]);

    $this->assertEquals($this->mysql(), Eloquent::connection());
    $this->assertSame('db.example', $wpdb->parsedHost);
  }

  #[RunInSeparateProcess]
  #[PreserveGlobalState(false)]
  public function test_a_port_in_db_host_becomes_the_port(): void
  {
    $this->defineMysqlConstants('db.example:3307');
    $wpdb = $this->wordpress(['db.example', 3307, null, false]);

    $this->assertEquals($this->mysql(['port' => 3307]), Eloquent::connection());
    $this->assertSame('db.example:3307', $wpdb->parsedHost);
  }

  #[RunInSeparateProcess]
  #[PreserveGlobalState(false)]
  public function test_a_socket_in_db_host_becomes_the_unix_socket(): void
  {
    $this->defineMysqlConstants('localhost:/tmp/mysql.sock');
    $this->wordpress(['localhost', null, '/tmp/mysql.sock', false]);

    $this->assertEquals(
      $this->mysql(['host' => 'localhost', 'unix_socket' => '/tmp/mysql.sock']),
      Eloquent::connection()
    );
  }

  #[RunInSeparateProcess]
  #[PreserveGlobalState(false)]
  public function test_an_ipv6_host_is_bracketed_as_wordpress_does_for_mysqlnd(): void
  {
    $this->defineMysqlConstants('[::1]:3306');
    $this->wordpress(['::1', 3306, null, true]);

    $host = extension_loaded('mysqlnd') ? '[::1]' : '::1';

    $this->assertEquals($this->mysql(['host' => $host, 'port' => 3306]), Eloquent::connection());
  }

  #[RunInSeparateProcess]
  #[PreserveGlobalState(false)]
  public function test_an_empty_collation_is_left_out(): void
  {
    $this->defineMysqlConstants();
    $this->wordpress(['db.example', null, null, false], 'utf8mb4', '');

    $connection = Eloquent::connection();

    $this->assertArrayNotHasKey('collation', $connection);
    $this->assertSame('utf8mb4', $connection['charset']);
  }

  #[RunInSeparateProcess]
  #[PreserveGlobalState(false)]
  public function test_a_db_host_wordpress_cannot_parse_is_passed_as_it_is(): void
  {
    $this->defineMysqlConstants('db.example');
    $this->wordpress(false);

    $this->assertEquals($this->mysql(), Eloquent::connection());
  }

  #[RunInSeparateProcess]
  #[PreserveGlobalState(false)]
  public function test_without_wpdb_the_connection_still_defaults_to_utf8mb4(): void
  {
    $this->defineMysqlConstants('db.example:3307');
    $GLOBALS['wpdb'] = null;

    $connection = Eloquent::connection();

    $this->assertSame('db.example:3307', $connection['host']);
    $this->assertSame('utf8mb4', $connection['charset']);
    $this->assertArrayNotHasKey('collation', $connection);
  }

  #[RunInSeparateProcess]
  #[PreserveGlobalState(false)]
  public function test_db_engine_mysql_keeps_mysql(): void
  {
    // What the SQLite Database Integration plugin defines when its drop-in is not loaded.
    define('DB_ENGINE', 'mysql');
    define('FQDB', '/site/wp-content/database/.ht.sqlite');
    $this->defineMysqlConstants();
    $this->wordpress(['db.example', null, null, false]);

    $this->assertEquals($this->mysql(), Eloquent::connection());
  }

  #[RunInSeparateProcess]
  #[PreserveGlobalState(false)]
  public function test_sqlite_uses_the_file_wordpress_uses(): void
  {
    // WordPress Playground, measured on 2026-09-25 (SQLite Database Integration 3.0.2).
    define('DB_ENGINE', 'sqlite');
    define('FQDB', '/wordpress/wp-content/database/.ht.sqlite');
    // Playground's wp-config still carries the sample MySQL values: they must not be used.
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'database_name_here');
    define('DB_USER', 'username_here');
    define('DB_PASSWORD', 'password_here');

    $this->assertSame(
      [
        'driver' => 'sqlite',
        'database' => '/wordpress/wp-content/database/.ht.sqlite',
        'prefix' => '',
      ],
      Eloquent::connection()
    );
  }

  #[RunInSeparateProcess]
  #[PreserveGlobalState(false)]
  public function test_sqlite_without_a_database_file_falls_back_to_mysql(): void
  {
    define('DB_ENGINE', 'sqlite');
    $this->defineMysqlConstants();
    $this->wordpress(['db.example', null, null, false]);

    $this->assertEquals($this->mysql(), Eloquent::connection());
  }
}
