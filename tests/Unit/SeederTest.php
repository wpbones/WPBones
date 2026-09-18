<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Database\Seeder;
use WPKirk\WPBones\Tests\Support\WpdbSpy;

final class SeederTest extends TestCase
{
  private WpdbSpy $wpdb;

  protected function setUp(): void
  {
    $this->wpdb = $GLOBALS['wpdb'];
    $this->wpdb->reset();
    $this->wpdb->prefix = 'wp_';
  }

  public function test_run_inserts_into_the_prefixed_quoted_table(): void
  {
    new class extends Seeder {
      protected $tablename = 'books';

      public function run()
      {
        $this->insert("(title) VALUES ('a')");
      }
    };

    $this->assertSame(["INSERT INTO `wp_books` (title) VALUES ('a')"], $this->wpdb->queries);
  }

  public function test_run_once_counts_first_then_runs(): void
  {
    new class extends Seeder {
      protected $tablename = 'books';
      protected $runOnce = true;

      public function run()
      {
        $this->truncate();
        $this->truncate('other');
      }
    };

    $this->assertSame(
      ['SELECT COUNT(*) FROM `wp_books`', 'TRUNCATE TABLE `wp_books`', 'TRUNCATE TABLE `wp_other`'],
      $this->wpdb->queries
    );
  }

  public function test_a_table_name_that_is_not_an_identifier_is_refused_before_any_query(): void
  {
    try {
      new class extends Seeder {
        protected $tablename = 'books` WHERE 1; --';

        public function run()
        {
          $this->insert("(title) VALUES ('a')");
        }
      };
    } catch (InvalidArgumentException $e) {
      $this->assertStringContainsString('Invalid table name', $e->getMessage());
      $this->assertSame([], $this->wpdb->queries);

      return;
    }

    $this->fail('expected InvalidArgumentException was not thrown');
  }

  public function test_truncate_refuses_an_explicit_name_that_is_not_an_identifier(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid table name');

    new class extends Seeder {
      protected $tablename = 'books';

      public function run()
      {
        $this->truncate('other`; DROP TABLE wp_users; --');
      }
    };
  }
}
