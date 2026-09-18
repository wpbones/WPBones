<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Database\Migrations\Migration;
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

  public function test_create_refuses_a_table_name_that_is_not_an_identifier_before_touching_the_database(): void
  {
    try {
      new class extends Migration {
        public function up()
        {
          $this->create('books` (id INT); DROP TABLE wp_users; --', '(id INT)');
        }
      };
    } catch (InvalidArgumentException $e) {
      $this->assertStringContainsString('Invalid table name', $e->getMessage());
      $this->assertSame([], $this->wpdb->queries);

      return;
    }

    $this->fail('expected InvalidArgumentException was not thrown');
  }

  public function test_constructor_reads_the_charset_collate_and_calls_up(): void
  {
    $migration = new class extends Migration {
      public bool $ran = false;

      public function up()
      {
        $this->ran = true;
      }
    };

    $this->assertTrue($migration->ran);
    $this->assertSame([], $this->wpdb->queries, 'no SQL is issued until create() is called');
  }
}
