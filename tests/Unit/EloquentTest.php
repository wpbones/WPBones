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
 */
final class EloquentTest extends TestCase
{
  private const MYSQL = [
    'driver' => 'mysql',
    'host' => 'db.example',
    'database' => 'wordpress',
    'username' => 'wp',
    'password' => 'secret',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
  ];

  private function defineMysqlConstants(): void
  {
    define('DB_HOST', 'db.example');
    define('DB_NAME', 'wordpress');
    define('DB_USER', 'wp');
    define('DB_PASSWORD', 'secret');
  }

  #[RunInSeparateProcess]
  #[PreserveGlobalState(false)]
  public function test_mysql_is_the_default_and_unchanged(): void
  {
    $this->defineMysqlConstants();

    $this->assertSame(self::MYSQL, Eloquent::connection());
  }

  #[RunInSeparateProcess]
  #[PreserveGlobalState(false)]
  public function test_db_engine_mysql_keeps_mysql(): void
  {
    // What the SQLite Database Integration plugin defines when its drop-in is not loaded.
    define('DB_ENGINE', 'mysql');
    define('FQDB', '/site/wp-content/database/.ht.sqlite');
    $this->defineMysqlConstants();

    $this->assertSame(self::MYSQL, Eloquent::connection());
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

    $this->assertSame(self::MYSQL, Eloquent::connection());
  }
}
