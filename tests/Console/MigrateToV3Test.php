<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\BonesProcess;
use WPKirk\WPBones\Tests\Support\WpdbSpy;

/**
 * `bones migrate:to-v3` converts a 2.x plugin's database folder (wpbones/WPBones#40): migrations
 * move to the 3.0 base class, and every seeder becomes a migration, because 3.0 runs no seeders.
 *
 * The CLI runs as a process over a throwaway plugin, answering "y" on stdin. The migrations it
 * writes are then included here and run against the $wpdb spy, so the test asserts the SQL the
 * converted code sends, not only how it reads.
 */
#[Group('console')]
final class MigrateToV3Test extends TestCase
{
  private BonesProcess $bones;

  private WpdbSpy $wpdb;

  protected function setUp(): void
  {
    parent::setUp();

    $this->bones = new BonesProcess();
    $this->wpdb = $GLOBALS['wpdb'];
    $this->wpdb->reset();
    $this->wpdb->prefix = 'wp_';

    mkdir($this->bones->plugin . '/database/migrations', 0777, true);
    mkdir($this->bones->plugin . '/database/seeders', 0777, true);
  }

  protected function tearDown(): void
  {
    $this->bones->remove();
    parent::tearDown();
  }

  private function put(string $path, string $code): void
  {
    file_put_contents($this->bones->plugin . '/' . $path, $code);
  }

  private function convert(string $answer = "y\n"): array
  {
    return $this->bones->run(['migrate:to-v3'], $answer);
  }

  /**
   * The migration a seeder became.
   */
  private function converted(string $suffix): string
  {
    $files = glob($this->bones->plugin . "/database/migrations/*_{$suffix}.php") ?: [];
    $this->assertCount(1, $files, "one migration ending in _{$suffix}.php");

    return $files[0];
  }

  /**
   * Include a generated migration and run it, as the Migrator does.
   *
   * @return string[] The SQL it sent.
   */
  private function runMigration(string $file, int $rows = 0): array
  {
    $this->wpdb->reset();
    $this->wpdb->var = $rows;

    $migration = include $file;
    $migration->up();

    return $this->wpdb->queries;
  }

  private function assertParses(string $file): void
  {
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $lint, $status);
    $this->assertSame(0, $status, implode("\n", $lint));
  }

  public function test_migrations_move_to_the_3x_base_class(): void
  {
    $this->put('database/migrations/2015_12_12_000000_create_books_table.php', <<<'PHP'
      <?php
      use WPKirk\WPBones\Database\Migrations\Migration;

      return new class extends Migration {
        public function up()
        {
        }
      };
      PHP);
    $this->put('database/migrations/2016_01_01_000000_fully_qualified.php', <<<'PHP'
      <?php
      return new class extends \WPKirk\WPBones\Database\Migrations\Migration {
        public function up()
        {
        }
      };
      PHP);

    $run = $this->convert();

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString(
      'use WPKirk\WPBones\Database\Migration;',
      (string) file_get_contents($this->bones->plugin . '/database/migrations/2015_12_12_000000_create_books_table.php')
    );
    $this->assertStringContainsString(
      'extends \WPKirk\WPBones\Database\Migration {',
      (string) file_get_contents($this->bones->plugin . '/database/migrations/2016_01_01_000000_fully_qualified.php')
    );
  }

  public function test_a_run_once_seeder_becomes_a_migration_that_seeds_only_an_empty_table(): void
  {
    $this->put('database/seeders/BookSeeder.php', <<<'PHP'
      <?php

      use WPKirk\WPBones\Database\Seeder;

      return new class extends Seeder {
        protected $tablename = 'my_plugin_books';

        protected $usePrefix = false;

        // Run the database seeds just once
        protected $runOnce = true;

        public function run()
        {
          // insert by using the Seeder class
          $this->insert("(name) VALUES ('Book iMac')");

          // $this->insert("(name) VALUES ('commented out')");
        }
      };
      PHP);

    $run = $this->convert();

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertFileDoesNotExist($this->bones->plugin . '/database/seeders/BookSeeder.php');
    $this->assertDirectoryDoesNotExist($this->bones->plugin . '/database/seeders');

    $file = $this->converted('book_seeder');
    $this->assertParses($file);
    $code = (string) file_get_contents($file);
    $this->assertStringContainsString('use WPKirk\WPBones\Database\Migration;', $code);
    $this->assertStringNotContainsString('Seeder;', $code);
    $this->assertStringContainsString("// \$this->insert(\"(name) VALUES ('commented out')\");", $code, 'comments are left alone');

    $this->assertSame(
      ['SELECT COUNT(*) FROM `my_plugin_books`', "INSERT INTO `my_plugin_books` (name) VALUES ('Book iMac')"],
      $this->runMigration($file),
      'an empty table is seeded, without the prefix, as the seeder did'
    );
    $this->assertSame(['SELECT COUNT(*) FROM `my_plugin_books`'], $this->runMigration($file, 4), 'a seeded one is left alone');
  }

  public function test_a_seeder_that_ran_every_time_keeps_its_body_and_is_flagged_for_review(): void
  {
    $this->put('database/seeders/countriesTableSeeder.php', <<<'PHP'
      <?php

      use WPKirk\WPBones\Database\Seeder;

      return new class extends Seeder {

        protected $tablename = 'countries';

        public function run()
        {
          $this->truncate();

          $this->insert(
            "(id, country) VALUES (1, 'Mauritania')"
          );

          $this->truncate('other');
        }
      };
      PHP);

    $run = $this->convert();

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString('runs once now', $run['output']);

    $file = $this->converted('countries_table_seeder');
    $this->assertParses($file);
    $this->assertStringNotContainsString(", \n", (string) file_get_contents($file), 'no trailing space where the argument starts on the next line');
    $this->assertSame(
      ['TRUNCATE TABLE `wp_countries`', "INSERT INTO `wp_countries` (id, country) VALUES (1, 'Mauritania')", 'TRUNCATE TABLE `wp_other`'],
      $this->runMigration($file)
    );
  }

  public function test_the_seeders_own_table_name_and_count_keep_working(): void
  {
    $this->put('database/seeders/Raw.php', <<<'PHP'
      <?php

      use WPKirk\WPBones\Database\Seeder;

      return new class extends Seeder {
        protected $tablename = 'items';

        public function run()
        {
          if ($this->count() === 0) {
            $this->query("UPDATE {$this->tablename} SET name = 'x'");
          }
        }
      };
      PHP);

    $this->convert();

    $file = $this->converted('raw');
    $this->assertParses($file);
    $this->assertSame(['SELECT COUNT(*) FROM `wp_items`', "UPDATE wp_items SET name = 'x'"], $this->runMigration($file));
  }

  public function test_converted_seeders_run_after_every_migration_in_the_order_they_ran(): void
  {
    $this->put('database/migrations/2030_01_01_000000_create_items.php', "<?php\nreturn new class extends \\WPKirk\\WPBones\\Database\\Migration { public function up() {} };\n");

    foreach (['ASeeder', 'BSeeder'] as $name) {
      $this->put("database/seeders/{$name}.php", "<?php\nuse WPKirk\\WPBones\\Database\\Seeder;\nreturn new class extends Seeder { public function run() {} };\n");
    }

    $this->convert();

    $names = array_map(fn($file) => basename($file, '.php'), glob($this->bones->plugin . '/database/migrations/*.php') ?: []);
    sort($names, SORT_STRING);

    // 2.x ran every seeder after every migration: a migration dated later than today still
    // comes first.
    $this->assertSame(
      ['2030_01_01_000000_create_items', '2030_01_01_000001_a_seeder', '2030_01_01_000002_b_seeder'],
      $names
    );
  }

  public function test_a_seeder_it_cannot_convert_is_kept_and_reported(): void
  {
    $this->put('database/seeders/Helper.php', <<<'PHP'
      <?php

      use WPKirk\WPBones\Database\Seeder;

      return new class extends Seeder {
        protected $tablename = 'items';

        public function run()
        {
          $this->insert($this->rows());
        }

        private function rows(): string
        {
          return "(name) VALUES ('a')";
        }
      };
      PHP);

    $run = $this->convert();

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString('database/seeders/Helper.php: it declares methods besides run(): convert it by hand', $run['output']);
    $this->assertFileExists($this->bones->plugin . '/database/seeders/Helper.php');
    $this->assertSame([], glob($this->bones->plugin . '/database/migrations/*.php') ?: []);
  }

  public function test_answering_no_changes_nothing(): void
  {
    $this->put('database/seeders/BookSeeder.php', "<?php\nreturn new class extends \\WPKirk\\WPBones\\Database\\Seeder { protected \$tablename = 'b'; public function run() {} };\n");

    $run = $this->convert("n\n");

    $this->assertSame(1, $run['status'], $run['output']);
    $this->assertFileExists($this->bones->plugin . '/database/seeders/BookSeeder.php');
  }

  public function test_a_second_run_changes_nothing(): void
  {
    $this->put('database/seeders/BookSeeder.php', "<?php\nreturn new class extends \\WPKirk\\WPBones\\Database\\Seeder { protected \$tablename = 'b'; public function run() { \$this->insert(\"(x) VALUES (1)\"); } };\n");

    $this->convert();
    $before = array_map('file_get_contents', glob($this->bones->plugin . '/database/migrations/*.php') ?: []);

    $run = $this->convert();

    $this->assertStringContainsString('Nothing to change', $run['output']);
    $this->assertSame($before, array_map('file_get_contents', glob($this->bones->plugin . '/database/migrations/*.php') ?: []));
  }
}
