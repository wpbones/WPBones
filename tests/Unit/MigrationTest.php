<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Database\Migration;
use WPKirk\WPBones\Database\Migrations\Migration as LegacyMigration;
use WPKirk\WPBones\Tests\Support\WpdbSpy;

/**
 * Migration::create() ends in dbDelta(), a WordPress function, so only the guard that
 * runs before it is testable here. The happy path belongs to the WordPress-backed suite.
 */
final class MigrationTest extends TestCase
{
  private WpdbSpy $wpdb;

  protected function setUp(): void
  {
    $this->wpdb = $GLOBALS['wpdb'];
    $this->wpdb->reset();
    $this->wpdb->prefix = 'wp_';
  }

  public function test_creating_a_migration_runs_nothing(): void
  {
    $migration = new class extends Migration {
      public bool $ran = false;

      public function up()
      {
        $this->ran = true;
      }
    };

    $this->assertFalse($migration->ran, 'since 3.0 the Migrator calls up(), the constructor does not');
    $this->assertSame([], $this->wpdb->queries);
  }

  public function test_the_2x_class_name_still_loads_and_runs_nothing_either(): void
  {
    $migration = new class extends LegacyMigration {
      public bool $ran = false;

      public function up()
      {
        $this->ran = true;
      }
    };

    $this->assertInstanceOf(Migration::class, $migration);
    $this->assertFalse($migration->ran);
  }

  public function test_the_constructor_reads_the_charset_collate(): void
  {
    $migration = new class extends Migration {
      public function up()
      {
      }

      public function collate(): string
      {
        return $this->charsetCollate;
      }
    };

    $this->assertSame('DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci', $migration->collate());
  }

  public function test_create_refuses_a_table_name_that_is_not_an_identifier_before_touching_the_database(): void
  {
    $migration = new class extends Migration {
      public function up()
      {
        $this->create('books` (id INT); DROP TABLE wp_users; --', '(id INT)');
      }
    };

    try {
      $migration->up();
    } catch (InvalidArgumentException $e) {
      $this->assertStringContainsString('Invalid table name', $e->getMessage());
      $this->assertSame([], $this->wpdb->queries);

      return;
    }

    $this->fail('expected InvalidArgumentException was not thrown');
  }

  public function test_seed_helpers_write_to_the_prefixed_quoted_table(): void
  {
    $migration = new class extends Migration {
      public function up()
      {
        if ($this->isEmpty('books')) {
          $this->insert('books', "(title) VALUES ('a')");
        }

        $this->truncate('other');
      }
    };

    $migration->up();

    $this->assertSame(
      ['SELECT COUNT(*) FROM `wp_books`', "INSERT INTO `wp_books` (title) VALUES ('a')", 'TRUNCATE TABLE `wp_other`'],
      $this->wpdb->queries
    );
  }

  public function test_seed_helpers_honour_use_prefix(): void
  {
    $migration = new class extends Migration {
      protected $usePrefix = false;

      public function up()
      {
        $this->insert('books', "(title) VALUES ('a')");
      }
    };

    $migration->up();

    $this->assertSame(["INSERT INTO `books` (title) VALUES ('a')"], $this->wpdb->queries);
  }

  public function test_seed_helpers_refuse_a_table_name_that_is_not_an_identifier_before_any_query(): void
  {
    $migration = new class extends Migration {
      public function up()
      {
        $this->truncate('other`; DROP TABLE wp_users; --');
      }
    };

    try {
      $migration->up();
    } catch (InvalidArgumentException $e) {
      $this->assertStringContainsString('Invalid table name', $e->getMessage());
      $this->assertSame([], $this->wpdb->queries);

      return;
    }

    $this->fail('expected InvalidArgumentException was not thrown');
  }
}
