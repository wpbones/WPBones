<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Database\Migrations\Migrator;
use WPKirk\WPBones\Tests\Support\InMemoryMigrationRepository;

/**
 * The Migrator against real migration files in a scratch folder and its state in memory
 * (wpbones/WPBones#40). The WordPress side, the options and the lock under two requests, is
 * proven live by .claude/scripts/migrations-live-smoke.sh in the workspace.
 */
final class MigratorTest extends TestCase
{
  private string $path;

  private string $log;

  private string $errorLog;

  private InMemoryMigrationRepository $repository;

  protected function setUp(): void
  {
    $this->path = sys_get_temp_dir() . '/wpbones-migrator-' . bin2hex(random_bytes(6));
    mkdir($this->path);

    $this->log = tempnam(sys_get_temp_dir(), 'wpbones-migrator-log-');
    $this->errorLog = (string) ini_get('error_log');
    ini_set('error_log', $this->log);

    $this->repository = new InMemoryMigrationRepository();

    $GLOBALS['migrator_test_ran'] = [];
    $GLOBALS['EZSQL_ERROR'] = [];
  }

  protected function tearDown(): void
  {
    ini_set('error_log', $this->errorLog);
    unlink($this->log);

    foreach (glob($this->path . '/*') ?: [] as $file) {
      unlink($file);
    }

    rmdir($this->path);

    unset($GLOBALS['migrator_test_ran'], $GLOBALS['EZSQL_ERROR']);
  }

  /**
   * A migration file whose up() records its name, then runs $body.
   */
  private function migration(string $name, string $body = ''): void
  {
    file_put_contents(
      "{$this->path}/{$name}.php",
      <<<PHP
      <?php
      use WPKirk\\WPBones\\Database\\Migration;

      return new class extends Migration {
        public function up()
        {
          \$GLOBALS['migrator_test_ran'][] = '{$name}';
          {$body}
        }
      };
      PHP
    );
  }

  private function migrator(string $version = '1.0.0'): Migrator
  {
    return new Migrator($this->path, $version, $this->repository, 'Test Plugin');
  }

  private function ran(): array
  {
    return $GLOBALS['migrator_test_ran'];
  }

  public function test_a_site_never_migrated_is_due(): void
  {
    $this->assertTrue($this->migrator()->isDue());
  }

  public function test_a_failure_of_an_older_version_does_not_hold_back_the_next_one(): void
  {
    // 1.1.0 failed; 1.1.1 ships the fix, and every page may run it.
    $this->repository->version = '1.0.0';
    $this->repository->failure = ['migration' => 'x', 'message' => 'boom', 'version' => '1.1.0', 'time' => time()];

    $this->assertTrue($this->migrator('1.1.1')->isDue());
    $this->assertFalse($this->migrator('1.1.0')->isDue(), 'the version that failed is still held back');
  }

  public function test_a_failure_with_the_version_unchanged_is_retried_by_an_administrator_too(): void
  {
    // A migration added during development, without a version bump, failed.
    $this->repository->version = '1.0.0';
    $this->repository->failure = ['migration' => 'x', 'message' => 'boom', 'version' => '1.0.0', 'time' => time() - Migrator::RETRY_AFTER];

    $this->assertFalse($this->migrator('1.0.0')->isDue());
    $this->assertTrue($this->migrator('1.0.0')->isDue(true));
  }

  public function test_an_automatic_run_stops_when_another_request_failed_while_it_waited(): void
  {
    $this->migration('2026_01_01_000000_first');
    $this->repository->writtenMeanwhile = ['failure' => ['migration' => '2026_01_01_000000_first', 'message' => 'boom', 'version' => '1.0.0', 'time' => time()]];

    $result = $this->migrator()->migrate(true);

    $this->assertTrue($result->ok());
    $this->assertSame([], $this->ran(), 'a public page does not rerun what just failed');
  }

  public function test_an_automatic_run_stops_when_another_request_finished_while_it_waited(): void
  {
    $this->migration('2026_01_01_000000_first');
    $this->repository->writtenMeanwhile = ['version' => '1.0.0'];

    $this->migrator('1.0.0')->migrate(true);

    $this->assertSame([], $this->ran());
  }

  public function test_activation_and_the_cli_run_whatever_the_state(): void
  {
    $this->migration('2026_01_01_000000_first');
    $this->repository->writtenMeanwhile = ['failure' => ['migration' => 'other', 'message' => 'boom', 'version' => '1.0.0', 'time' => time()]];

    $result = $this->migrator()->migrate();

    $this->assertTrue($result->ok());
    $this->assertSame(['2026_01_01_000000_first'], $this->ran());
    $this->assertNull($this->repository->failure, 'and a complete run clears the failure');
  }

  public function test_a_migration_whose_record_cannot_be_stored_stops_the_run_and_keeps_the_version(): void
  {
    $this->migration('2026_01_01_000000_first');
    $this->migration('2026_01_02_000000_second');
    $this->repository->logFails = true;

    $result = $this->migrator()->migrate();

    $this->assertSame('2026_01_01_000000_first', $result->failed);
    $this->assertStringContainsString('could not be recorded', (string) $result->error);
    $this->assertSame(['2026_01_01_000000_first'], $this->ran(), 'the second did not run');
    $this->assertNull($this->repository->version);
    $this->assertSame('2026_01_01_000000_first', $this->repository->failure['migration']);
  }

  public function test_a_version_that_cannot_be_stored_does_not_finish_the_update(): void
  {
    $this->migration('2026_01_01_000000_first');
    $this->repository->versionFails = true;

    $result = $this->migrator()->migrate();

    $this->assertSame(['2026_01_01_000000_first'], $result->ran);
    $this->assertFalse($result->advanced, 'plugin/updated.php is left to the request that stores it');
    $this->assertStringContainsString('the version 1.0.0 could not be stored', (string) file_get_contents($this->log));

    $this->repository->versionFails = false;
    $again = $this->migrator()->migrate();

    $this->assertSame([], $again->ran);
    $this->assertTrue($again->advanced);
  }

  public function test_a_site_migrated_for_this_version_is_not_due(): void
  {
    $this->repository->version = '1.0.0';

    $this->assertFalse($this->migrator()->isDue());
    $this->assertFalse($this->migrator()->isDue(true));
  }

  public function test_any_other_stored_version_is_due_downgrades_included(): void
  {
    $this->repository->version = '1.1.0';

    $this->assertTrue($this->migrator('1.0.0')->isDue());
  }

  public function test_version_changed_compares_the_stored_version_with_the_plugins(): void
  {
    $this->assertTrue($this->migrator('1.0.0')->versionChanged(), 'never migrated');

    $this->repository->version = '1.0.0';
    $this->assertFalse($this->migrator('1.0.0')->versionChanged());
    $this->assertTrue($this->migrator('1.0.1')->versionChanged());
  }

  public function test_after_a_failure_only_an_administrator_retries_and_not_at_once(): void
  {
    $this->repository->failure = ['migration' => 'x', 'message' => 'boom', 'version' => '1.0.0', 'time' => time()];

    $this->assertFalse($this->migrator()->isDue(), 'a public page never retries');
    $this->assertFalse($this->migrator()->isDue(true), 'an admin page waits RETRY_AFTER seconds');

    $this->repository->failure['time'] = time() - Migrator::RETRY_AFTER;

    $this->assertFalse($this->migrator()->isDue());
    $this->assertTrue($this->migrator()->isDue(true));
  }

  public function test_pending_migrations_run_once_in_file_name_order(): void
  {
    $this->migration('2026_01_02_000000_second');
    $this->migration('2026_01_01_000000_first');
    $this->migration('2026_01_03_000000_third');

    $result = $this->migrator()->migrate();

    $this->assertTrue($result->ok());
    $this->assertSame(['2026_01_01_000000_first', '2026_01_02_000000_second', '2026_01_03_000000_third'], $this->ran());
    $this->assertSame($this->ran(), $result->ran);
    $this->assertSame([1, 1, 1], array_column($this->repository->ran, 'batch'));
    $this->assertSame(['1.0.0', '1.0.0', '1.0.0'], array_column($this->repository->ran, 'version'));

    $again = $this->migrator()->migrate();

    $this->assertSame([], $again->ran, 'a migration that ran does not run again');
    $this->assertCount(3, $this->ran());
  }

  public function test_the_first_run_records_the_version_and_reports_no_previous_one(): void
  {
    $this->migration('2026_01_01_000000_first');

    $result = $this->migrator('1.0.0')->migrate();

    $this->assertTrue($result->advanced);
    $this->assertNull($result->previous);
    $this->assertSame('1.0.0', $this->repository->version);
  }

  public function test_an_update_runs_only_the_new_migration_in_a_new_batch(): void
  {
    $this->migration('2026_01_01_000000_first');
    $this->migrator('1.0.0')->migrate();

    $this->migration('2026_02_01_000000_added_in_1_1');
    $result = $this->migrator('1.1.0')->migrate();

    $this->assertSame(['2026_02_01_000000_added_in_1_1'], $result->ran);
    $this->assertSame('1.0.0', $result->previous);
    $this->assertTrue($result->advanced);
    $this->assertSame(2, $this->repository->ran['2026_02_01_000000_added_in_1_1']['batch']);
    $this->assertSame('1.1.0', $this->repository->version);
  }

  public function test_a_version_change_with_nothing_new_still_advances_the_version(): void
  {
    $this->migration('2026_01_01_000000_first');
    $this->migrator('1.0.0')->migrate();

    $result = $this->migrator('1.0.1')->migrate();

    $this->assertSame([], $result->ran);
    $this->assertTrue($result->advanced);
    $this->assertSame('1.0.1', $this->repository->version);
  }

  public function test_a_migration_added_without_a_version_bump_runs_when_asked(): void
  {
    $this->migration('2026_01_01_000000_first');
    $this->migrator('1.0.0')->migrate();

    $this->migration('2026_01_05_000000_during_development');

    $this->assertFalse($this->migrator('1.0.0')->isDue(), 'the cheap check sees no version change');

    $result = $this->migrator('1.0.0')->migrate();

    $this->assertSame(['2026_01_05_000000_during_development'], $result->ran);
    $this->assertFalse($result->advanced);
  }

  public function test_a_pending_migration_that_sorts_before_one_that_ran_still_runs_with_a_warning(): void
  {
    $this->migration('2026_03_01_000000_merged_first');
    $this->migrator('1.0.0')->migrate();

    // A branch created earlier, merged later.
    $this->migration('2026_02_01_000000_merged_later');

    $this->assertSame(['2026_02_01_000000_merged_later'], $this->migrator('1.1.0')->outOfOrder());

    $result = $this->migrator('1.1.0')->migrate();

    $this->assertSame(['2026_02_01_000000_merged_later'], $result->ran);
    $this->assertSame(['2026_02_01_000000_merged_later'], $result->outOfOrder);
    $this->assertStringContainsString(
      '[WP Bones] Test Plugin: migration 2026_02_01_000000_merged_later runs after migrations whose names sort later',
      (string) file_get_contents($this->log)
    );
    $this->assertSame([], $this->migrator('1.1.0')->outOfOrder(), 'once it ran it is no longer pending');
  }

  public function test_a_failure_stops_the_run_and_is_not_recorded(): void
  {
    $this->migration('2026_01_01_000000_ok');
    $this->migration('2026_01_02_000000_broken', "throw new \\RuntimeException('no such column');");
    $this->migration('2026_01_03_000000_after');

    $result = $this->migrator('1.1.0')->migrate();

    $this->assertFalse($result->ok());
    $this->assertSame('2026_01_02_000000_broken', $result->failed);
    $this->assertSame('RuntimeException: no such column', $result->error);
    $this->assertSame(['2026_01_01_000000_ok', '2026_01_02_000000_broken'], $this->ran(), 'the one after it did not run');
    $this->assertSame(['2026_01_01_000000_ok'], array_keys($this->repository->ran));
    $this->assertNull($this->repository->version, 'the version does not move, so the next request starts from it');
    $this->assertSame('2026_01_02_000000_broken', $this->repository->failure['migration']);
    $this->assertSame('RuntimeException: no such column', $this->repository->failure['message']);
    $this->assertFalse($result->advanced);
    $this->assertStringContainsString('migration 2026_01_02_000000_broken failed', (string) file_get_contents($this->log));
  }

  public function test_once_fixed_the_next_run_resumes_from_the_failed_migration_and_clears_the_failure(): void
  {
    $this->migration('2026_01_01_000000_ok');
    $this->migration('2026_01_02_000000_broken', "throw new \\RuntimeException('no such column');");
    $this->migration('2026_01_03_000000_after');
    $this->migrator('1.1.0')->migrate();

    $this->migration('2026_01_02_000000_broken');
    $GLOBALS['migrator_test_ran'] = [];

    $result = $this->migrator('1.1.0')->migrate();

    $this->assertTrue($result->ok());
    $this->assertSame(['2026_01_02_000000_broken', '2026_01_03_000000_after'], $this->ran());
    $this->assertNull($this->repository->failure);
    $this->assertSame('1.1.0', $this->repository->version);
  }

  public function test_a_database_error_left_by_a_migration_is_a_failure(): void
  {
    $this->migration(
      '2026_01_01_000000_bad_sql',
      "\$GLOBALS['EZSQL_ERROR'][] = ['query' => 'ALTER TABLE wp_books ADD COLUMN sku', 'error_str' => \"Duplicate column name 'sku'\"];"
    );

    $result = $this->migrator()->migrate();

    $this->assertSame('2026_01_01_000000_bad_sql', $result->failed);
    $this->assertSame("database error: Duplicate column name 'sku'", $result->error);
    $this->assertSame([], $this->repository->ran);
  }

  public function test_the_describe_dbdelta_runs_on_a_table_it_is_about_to_create_is_not_a_failure(): void
  {
    $this->migration(
      '2026_01_01_000000_create_books',
      "\$GLOBALS['EZSQL_ERROR'][] = ['query' => 'DESCRIBE wp_books;', 'error_str' => \"Table 'wp.wp_books' doesn't exist\"];"
    );

    $result = $this->migrator()->migrate();

    $this->assertTrue($result->ok());
    $this->assertSame(['2026_01_01_000000_create_books'], $result->ran);
  }

  public function test_errors_create_tolerated_are_not_a_failure_but_one_more_is(): void
  {
    $error = "['query' => 'ALTER TABLE wp_books ALTER COLUMN `id` SET DEFAULT \\'\\'', 'error_str' => 'Invalid default value']";

    // What Migration::create() does when dbDelta() fails at something cosmetic.
    $this->migration('2026_01_01_000000_tolerated', "\$GLOBALS['EZSQL_ERROR'][] = {$error}; \$this->toleratedErrors[] = {$error};");
    $this->migration('2026_01_02_000000_same_error_twice', "\$GLOBALS['EZSQL_ERROR'][] = {$error}; \$GLOBALS['EZSQL_ERROR'][] = {$error}; \$this->toleratedErrors[] = {$error};");

    $result = $this->migrator()->migrate();

    $this->assertSame(['2026_01_01_000000_tolerated'], $result->ran);
    $this->assertSame('2026_01_02_000000_same_error_twice', $result->failed, 'a tolerated error excuses one occurrence, not two');
  }

  public function test_errors_from_before_the_migration_are_not_blamed_on_it(): void
  {
    $GLOBALS['EZSQL_ERROR'][] = ['query' => 'SELECT nope', 'error_str' => 'an earlier error'];
    $this->migration('2026_01_01_000000_fine');

    $this->assertTrue($this->migrator()->migrate()->ok());
  }

  public function test_a_file_that_does_not_return_a_migration_is_a_failure(): void
  {
    file_put_contents("{$this->path}/2026_01_01_000000_not_a_migration.php", '<?php // forgot the return');

    $result = $this->migrator()->migrate();

    $this->assertSame('2026_01_01_000000_not_a_migration', $result->failed);
    $this->assertStringContainsString('does not return a migration', (string) $result->error);
  }

  public function test_a_file_written_for_2x_runs_once_through_the_old_class_name(): void
  {
    file_put_contents(
      "{$this->path}/2015_12_12_134527_create_products_table.php",
      <<<'PHP'
      <?php
      use WPKirk\WPBones\Database\Migrations\Migration;

      return new class extends Migration {
        public function up()
        {
          $GLOBALS['migrator_test_ran'][] = 'legacy';
        }
      };
      PHP
    );

    $this->migrator()->migrate();
    $this->migrator()->migrate();

    $this->assertSame(['legacy'], $this->ran(), 'included once by the Migrator, and up() called once');
  }

  public function test_nothing_runs_while_another_request_holds_the_lock(): void
  {
    $this->migration('2026_01_01_000000_first');
    $this->repository->heldElsewhere = true;

    $result = $this->migrator()->migrate();

    $this->assertTrue($result->locked);
    $this->assertFalse($result->ok());
    $this->assertSame([], $this->ran());
    $this->assertSame(['lock'], $this->repository->calls, 'no unlock of a lock it does not hold');
  }

  public function test_the_state_is_read_again_under_the_lock_and_the_lock_is_released(): void
  {
    $this->migration('2026_01_01_000000_first');

    $this->migrator()->migrate();

    $this->assertSame(
      ['lock', 'refresh', 'touch', 'log 2026_01_01_000000_first', 'failure cleared', 'version 1.0.0', 'unlock'],
      $this->repository->calls,
      'the lock is touched before each migration; the failure option is written even empty, to stay autoloaded'
    );
  }

  public function test_the_lock_is_released_when_a_migration_fails(): void
  {
    $this->migration('2026_01_01_000000_broken', "throw new \\RuntimeException('boom');");

    $this->migrator()->migrate();

    $this->assertFalse($this->repository->locked);
    $this->assertSame('unlock', end($this->repository->calls));
  }

  public function test_status_lists_every_migration_with_its_state(): void
  {
    $this->migration('2026_03_01_000000_ran');
    $this->migrator('1.0.0')->migrate();
    $this->migration('2026_02_01_000000_late');
    $this->migration('2026_04_01_000000_pending');

    $status = $this->migrator('1.0.0')->status();

    $this->assertSame(['2026_02_01_000000_late', '2026_03_01_000000_ran', '2026_04_01_000000_pending'], array_keys($status));
    $this->assertTrue($status['2026_03_01_000000_ran']['ran']);
    $this->assertSame(1, $status['2026_03_01_000000_ran']['batch']);
    $this->assertSame('1.0.0', $status['2026_03_01_000000_ran']['version']);
    $this->assertFalse($status['2026_04_01_000000_pending']['ran']);
    $this->assertFalse($status['2026_04_01_000000_pending']['outOfOrder']);
    $this->assertTrue($status['2026_02_01_000000_late']['outOfOrder']);
  }

  public function test_a_plugin_without_a_migrations_folder_just_records_its_version(): void
  {
    $migrator = new Migrator($this->path . '/missing', '1.0.0', $this->repository);

    $this->assertSame([], $migrator->files());

    $result = $migrator->migrate();

    $this->assertTrue($result->ok());
    $this->assertSame('1.0.0', $this->repository->version);
  }
}
