<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WPKirk\WPBones\Database\Migration;
use WPKirk\WPBones\Database\Migrations\Migration as LegacyMigration;
use WPKirk\WPBones\Tests\Support\WpdbSpy;

/**
 * Migration::create() ends in dbDelta(), a WordPress function: here it is replaced by a stand-in
 * that records the SQL and leaves errors behind, to test what create() concludes from them. Real
 * tables are created live by .claude/scripts/migrations-live-smoke.sh in the workspace.
 */
final class MigrationTest extends TestCase
{
  private WpdbSpy $wpdb;

  private string $errorLog;

  private string $log;

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $this->wpdb = $GLOBALS['wpdb'];
    $this->wpdb->reset();
    $this->wpdb->prefix = 'wp_';

    $GLOBALS['EZSQL_ERROR'] = [];
    $this->log = (string) tempnam(sys_get_temp_dir(), 'wpbones-migration-log-');
    $this->errorLog = (string) ini_get('error_log');
    ini_set('error_log', $this->log);
  }

  protected function tearDown(): void
  {
    ini_set('error_log', $this->errorLog);
    unlink($this->log);
    unset($GLOBALS['EZSQL_ERROR']);

    Monkey\tearDown();
    parent::tearDown();
  }

  /**
   * dbDelta() as WordPress runs it, reduced to what create() can observe: the SQL it was given,
   * and the errors it leaves in $EZSQL_ERROR.
   *
   * @param array<int, array{query: string, error_str: string}> $errors
   */
  private function dbDelta(array $errors = []): object
  {
    $calls = new \ArrayObject();

    Functions\when('dbDelta')->alias(function ($sql) use ($calls, $errors) {
      $calls[] = $sql;

      foreach ($errors as $error) {
        $GLOBALS['EZSQL_ERROR'][] = $error;
      }

      return [];
    });

    return $calls;
  }

  private function books(): object
  {
    return new class extends \WPKirk\WPBones\Database\Migration {
      public function up()
      {
        $this->create(
          'books',
          "(
            id bigint(20) unsigned NOT NULL auto_increment,
            `title` varchar(20) NOT NULL default '',
            sku varchar(20) NOT NULL default '',
            PRIMARY KEY  (id),
            KEY title (title)
          ) {$this->charsetCollate};"
        );
      }
    };
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

  public function test_create_hands_the_table_to_dbdelta_and_checks_its_columns(): void
  {
    $calls = $this->dbDelta();
    $this->wpdb->columns = ['id', 'title', 'sku'];

    $migration = $this->books();
    $migration->up();

    $this->assertCount(1, $calls);
    $this->assertStringStartsWith('CREATE TABLE wp_books (', $calls[0]);
    $this->assertStringEndsWith('COLLATE utf8mb4_unicode_520_ci;', $calls[0]);
    $this->assertSame(['DESCRIBE `wp_books`'], $this->wpdb->queries);
    $this->assertSame([], $migration->toleratedErrors());
  }

  public function test_create_fails_when_dbdelta_did_not_create_the_table(): void
  {
    $this->dbDelta([['query' => 'CREATE TABLE wp_books (...)', 'error_str' => 'You have an error in your SQL syntax']]);

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('dbDelta() did not create wp_books: You have an error in your SQL syntax');

    $this->books()->up();
  }

  public function test_create_fails_when_a_declared_column_is_missing(): void
  {
    $this->dbDelta([['query' => 'ALTER TABLE wp_books ADD COLUMN sku varchar(20)', 'error_str' => 'Disk full']]);
    $this->wpdb->columns = ['ID', 'Title'];

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('dbDelta() did not add sku to wp_books: Disk full');

    $this->books()->up();
  }

  public function test_create_tolerates_what_dbdelta_could_not_reconcile_when_the_columns_are_there(): void
  {
    $default = ['query' => "ALTER TABLE wp_books ALTER COLUMN `id` SET DEFAULT ''", 'error_str' => "Invalid default value for 'id'"];
    $probe = ['query' => 'DESCRIBE wp_books;', 'error_str' => "Table 'wp.wp_books' doesn't exist"];
    $this->dbDelta([$probe, $default]);
    $this->wpdb->columns = ['id', 'title', 'sku'];

    $migration = $this->books();
    $migration->up();

    $this->assertSame([$probe, $default], $migration->toleratedErrors());

    $log = (string) file_get_contents($this->log);
    $this->assertStringContainsString("dbDelta() left wp_books as it was for: ALTER TABLE wp_books ALTER COLUMN `id` SET DEFAULT '' (Invalid default value for 'id')", $log);
    $this->assertStringNotContainsString('DESCRIBE', $log, 'the probe of a new table is not news');
  }

  public function test_a_schema_on_one_line_declares_only_its_first_word_as_dbdelta_reads_it(): void
  {
    $this->dbDelta();
    $this->wpdb->columns = ['id', 'name'];

    $migration = new class extends \WPKirk\WPBones\Database\Migration {
      public function up()
      {
        $this->create('items', "(id bigint(20) unsigned NOT NULL auto_increment, name varchar(20) NOT NULL default '', PRIMARY KEY  (id))");
      }
    };

    $migration->up();

    $this->assertSame([], $migration->toleratedErrors());
  }

  public function test_constraints_are_not_columns(): void
  {
    $this->dbDelta();
    $this->wpdb->columns = ['id', 'book_id'];

    $migration = new class extends Migration {
      public function up()
      {
        $this->create(
          'reviews',
          "(
            id bigint(20) unsigned NOT NULL auto_increment,
            book_id bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (id),
            CONSTRAINT fk_book FOREIGN KEY (book_id) REFERENCES wp_books (id),
            FOREIGN KEY (book_id) REFERENCES wp_books (id),
            CHECK (book_id > 0)
          )"
        );
      }
    };

    $migration->up();

    $this->assertSame([], $migration->toleratedErrors(), 'nothing reported missing');
  }

  public function test_a_helper_the_database_refuses_throws_with_the_reason(): void
  {
    $this->wpdb->queryResult = false;
    $this->wpdb->last_error = "Data too long for column 'name' at row 1";

    $migration = new class extends Migration {
      public function up()
      {
        $this->insert('books', "(name) VALUES ('" . str_repeat('x', 300) . "')");
      }
    };

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage("The database refused INSERT: Data too long for column 'name' at row 1");

    $migration->up();
  }

  public function test_count_throws_when_the_table_cannot_be_read(): void
  {
    $this->wpdb->var = null;
    $this->wpdb->last_error = "Table 'wp.wp_books' doesn't exist";

    $migration = new class extends Migration {
      public function up()
      {
        $this->isEmpty('books');
      }
    };

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage("Could not count the rows of wp_books: Table 'wp.wp_books' doesn't exist");

    $migration->up();
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
