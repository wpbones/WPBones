<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Database\Migrations\OptionRepository;

/**
 * The SQL OptionRepository sends, against a $wpdb that answers from a script. Whether MySQL lets
 * exactly one of two racing requests take the lock is proven live by migrations-live-smoke.sh.
 */
final class OptionRepositoryTest extends TestCase
{
  private object $wpdb;

  private mixed $saved;

  /** @var array<string, array{0: mixed, 1: mixed}> update_option() calls: name => [value, autoload] */
  private array $updated = [];

  /** @var string[] */
  private array $deleted = [];

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $this->saved = $GLOBALS['wpdb'];
    $GLOBALS['wpdb'] = $this->wpdb = new class {
      public string $options = 'wp_options';

      /** @var string[] */
      public array $queries = [];

      /** @var array<int, mixed> what query() answers, in order */
      public array $queryResults = [];

      /** @var array<string, string|null> what get_var() answers, by option name in the SQL */
      public array $values = [];

      public function prepare(string $sql, ...$args): string
      {
        return vsprintf(str_replace('%s', "'%s'", $sql), array_map('addslashes', $args));
      }

      public function query(string $sql): int
      {
        $this->queries[] = $sql;

        return (int) array_shift($this->queryResults);
      }

      public function get_var(string $sql): ?string
      {
        $this->queries[] = $sql;

        foreach ($this->values as $name => $value) {
          if (str_contains($sql, "'{$name}'")) {
            return $value;
          }
        }

        return null;
      }
    };

    Functions\when('maybe_unserialize')->alias(fn($value) => is_string($value) && @unserialize($value) !== false ? unserialize($value) : $value);
    Functions\when('update_option')->alias(function ($name, $value, $autoload = null) {
      $this->updated[$name] = [$value, $autoload];

      return true;
    });
    Functions\when('delete_option')->alias(function ($name) {
      $this->deleted[] = $name;

      return true;
    });
    Functions\when('wp_cache_delete')->alias(function ($key, $group) {
      $this->cacheDeleted[] = "{$group}:{$key}";

      return true;
    });
  }

  /** @var string[] wp_cache_delete() calls, group:key */
  private array $cacheDeleted = [];

  protected function tearDown(): void
  {
    $GLOBALS['wpdb'] = $this->saved;
    Monkey\tearDown();
    parent::tearDown();
  }

  public function test_the_cheap_check_reads_the_autoloaded_option(): void
  {
    Functions\expect('get_option')->once()->with('my_plugin_slug_db_version', null)->andReturn('1.2.0');

    $this->assertSame('1.2.0', (new OptionRepository('my_plugin_slug'))->version());
    $this->assertSame([], $this->wpdb->queries, 'no query of its own');
  }

  public function test_an_empty_stored_version_counts_as_none(): void
  {
    Functions\when('get_option')->justReturn('');

    $this->assertNull((new OptionRepository('my_plugin_slug'))->version());
  }

  public function test_the_lock_is_an_insert_ignore_into_the_options_table(): void
  {
    $this->wpdb->queryResults = [1];

    $this->assertTrue((new OptionRepository('my_plugin_slug'))->lock());
    $this->assertCount(1, $this->wpdb->queries);
    $this->assertMatchesRegularExpression(
      "/^INSERT IGNORE INTO `wp_options` \\(`option_name`, `option_value`, `autoload`\\) VALUES \\('my_plugin_slug_migrations\\.lock', '\\d+\\|[0-9a-f]{16}', 'off'\\) \\/\\* LOCK \\*\\/$/",
      $this->wpdb->queries[0]
    );
  }

  public function test_unlock_deletes_the_row_only_while_it_is_still_this_requests(): void
  {
    $this->wpdb->queryResults = [1];
    $repository = new OptionRepository('my_plugin_slug');
    $repository->lock();
    preg_match("/'(\\d+\\|[0-9a-f]{16})'/", $this->wpdb->queries[0], $held);

    $repository->unlock();

    $this->assertSame(
      "DELETE FROM `wp_options` WHERE `option_name` = 'my_plugin_slug_migrations.lock' AND `option_value` = '{$held[1]}'",
      $this->wpdb->queries[1]
    );
  }

  public function test_touch_moves_the_time_of_the_lock_it_holds(): void
  {
    $this->wpdb->queryResults = [1, 1];
    $repository = new OptionRepository('my_plugin_slug');
    $repository->lock();
    preg_match("/'(\\d+\\|[0-9a-f]{16})'/", $this->wpdb->queries[0], $held);

    $repository->touch();

    $this->assertMatchesRegularExpression(
      "/^UPDATE `wp_options` SET `option_value` = '\\d+\\|[0-9a-f]{16}' WHERE `option_name` = 'my_plugin_slug_migrations\\.lock' AND `option_value` = '" . preg_quote($held[1], '/') . "'$/",
      $this->wpdb->queries[1]
    );
  }

  public function test_without_the_lock_touch_and_unlock_do_nothing(): void
  {
    $repository = new OptionRepository('my_plugin_slug');
    $repository->touch();
    $repository->unlock();

    $this->assertSame([], $this->wpdb->queries);
  }

  public function test_a_lock_held_by_another_request_is_refused(): void
  {
    $this->wpdb->queryResults = [0];
    $this->wpdb->values = ['my_plugin_slug_migrations.lock' => (time() - 30) . '|0123456789abcdef'];

    $this->assertFalse((new OptionRepository('my_plugin_slug'))->lock());
    $this->assertCount(2, $this->wpdb->queries, 'the insert and the read, no takeover');
  }

  public function test_an_abandoned_lock_is_taken_over_only_if_nobody_took_it_first(): void
  {
    $abandoned = (time() - OptionRepository::LOCK_TIMEOUT - 1) . '|0123456789abcdef';
    $this->wpdb->queryResults = [0, 1];
    $this->wpdb->values = ['my_plugin_slug_migrations.lock' => $abandoned];

    $this->assertTrue((new OptionRepository('my_plugin_slug'))->lock());
    $this->assertMatchesRegularExpression(
      "/^UPDATE `wp_options` SET `option_value` = '\\d+\\|[0-9a-f]{16}' WHERE `option_name` = 'my_plugin_slug_migrations\\.lock' AND `option_value` = '" . preg_quote($abandoned, '/') . "'$/",
      $this->wpdb->queries[2]
    );
  }

  public function test_losing_the_takeover_race_is_a_refusal(): void
  {
    $this->wpdb->queryResults = [0, 0];
    $this->wpdb->values = ['my_plugin_slug_migrations.lock' => (time() - OptionRepository::LOCK_TIMEOUT - 1) . '|0123456789abcdef'];

    $this->assertFalse((new OptionRepository('my_plugin_slug'))->lock());
  }

  public function test_refresh_reads_past_the_cache_and_wins_over_it(): void
  {
    Functions\when('get_option')->justReturn('1.0.0');

    $this->wpdb->values = [
      'my_plugin_slug_db_version' => '1.1.0',
      'my_plugin_slug_migrations' => serialize(['2026_01_01_000000_first' => ['batch' => 1, 'version' => '1.1.0', 'time' => 1]]),
      'my_plugin_slug_migrations_failure' => null,
    ];

    $repository = new OptionRepository('my_plugin_slug');
    $repository->refresh();

    $this->assertSame('1.1.0', $repository->version(), 'what another request wrote, not the cached 1.0.0');
    $this->assertSame(['2026_01_01_000000_first'], array_keys($repository->ran()));
    $this->assertNull($repository->failure());
  }

  public function test_refresh_sees_a_version_another_request_never_wrote_as_none(): void
  {
    Functions\when('get_option')->justReturn('1.0.0');

    $repository = new OptionRepository('my_plugin_slug');
    $repository->refresh();

    $this->assertNull($repository->version());
  }

  public function test_a_cache_that_disagrees_with_the_database_is_dropped(): void
  {
    Functions\when('get_option')->justReturn('1.0.0');
    $this->wpdb->values = ['my_plugin_slug_db_version' => '1.1.0'];

    (new OptionRepository('my_plugin_slug'))->refresh();

    $this->assertSame(
      ['options:alloptions', 'options:my_plugin_slug_db_version', 'options:my_plugin_slug_migrations_failure'],
      $this->cacheDeleted
    );
  }

  public function test_a_cache_that_agrees_is_left_alone(): void
  {
    Functions\when('get_option')->alias(fn($name) => $name === 'my_plugin_slug_db_version' ? '1.1.0' : '');
    $this->wpdb->values = ['my_plugin_slug_db_version' => '1.1.0', 'my_plugin_slug_migrations_failure' => ''];

    (new OptionRepository('my_plugin_slug'))->refresh();

    $this->assertSame([], $this->cacheDeleted);
  }

  public function test_forget_deletes_every_option_the_migrator_keeps(): void
  {
    OptionRepository::forget('my_plugin_slug');

    $this->assertSame(
      ['my_plugin_slug_db_version', 'my_plugin_slug_migrations', 'my_plugin_slug_migrations_failure', 'my_plugin_slug_migrations.lock'],
      $this->deleted
    );
  }

  public function test_log_appends_to_the_ledger_without_autoloading_it(): void
  {
    Functions\when('get_option')->justReturn(null);
    $this->wpdb->values = [
      'my_plugin_slug_migrations' => serialize(['2026_01_01_000000_first' => ['batch' => 1, 'version' => '1.0.0', 'time' => 1]]),
    ];

    $repository = new OptionRepository('my_plugin_slug');
    $repository->refresh();
    $repository->log('2026_02_01_000000_second', 2, '1.1.0');

    [$ledger, $autoload] = $this->updated['my_plugin_slug_migrations'];

    $this->assertSame(['2026_01_01_000000_first', '2026_02_01_000000_second'], array_keys($ledger));
    $this->assertSame(2, $ledger['2026_02_01_000000_second']['batch']);
    $this->assertSame('1.1.0', $ledger['2026_02_01_000000_second']['version']);
    $this->assertFalse($autoload);
    $this->assertSame($ledger, $repository->ran());
  }

  public function test_the_version_and_the_failure_are_autoloaded_and_no_failure_is_an_empty_value(): void
  {
    $repository = new OptionRepository('my_plugin_slug');
    $repository->setVersion('1.1.0');
    $repository->setFailure(['migration' => 'x', 'message' => 'boom', 'version' => '1.1.0', 'time' => 1]);

    $this->assertSame(['1.1.0', true], $this->updated['my_plugin_slug_db_version']);
    $this->assertSame('x', $this->updated['my_plugin_slug_migrations_failure'][0]['migration']);
    $this->assertTrue($this->updated['my_plugin_slug_migrations_failure'][1]);

    $repository->setFailure(null);

    // Kept, empty and autoloaded: the failure is read on every request, and a missing option
    // would cost a query each time.
    $this->assertSame(['', true], $this->updated['my_plugin_slug_migrations_failure']);
    $this->assertSame([], $this->deleted);
  }

  public function test_a_version_write_the_database_refused_is_reported(): void
  {
    Functions\when('update_option')->justReturn(false);

    $this->assertFalse((new OptionRepository('my_plugin_slug'))->setVersion('1.1.0'));

    $this->wpdb->values = ['my_plugin_slug_db_version' => '1.1.0'];
    $this->assertTrue((new OptionRepository('my_plugin_slug'))->setVersion('1.1.0'), 'unchanged is not refused');
  }

  public function test_an_empty_failure_reads_as_none(): void
  {
    Functions\when('get_option')->justReturn('');

    $this->assertNull((new OptionRepository('my_plugin_slug'))->failure());
  }

  public function test_a_ledger_write_the_database_refused_is_reported(): void
  {
    Functions\when('get_option')->justReturn(null);
    Functions\when('update_option')->justReturn(false);

    $repository = new OptionRepository('my_plugin_slug');
    $repository->refresh();

    $this->assertFalse($repository->log('2026_01_01_000000_first', 1, '1.0.0'));
  }

  public function test_update_option_answering_false_for_a_stored_value_is_not_a_refusal(): void
  {
    Functions\when('update_option')->justReturn(false);
    $this->wpdb->values = ['my_plugin_slug_migrations' => serialize(['2026_01_01_000000_first' => ['batch' => 1, 'version' => '1.0.0', 'time' => 1]])];

    $this->assertTrue((new OptionRepository('my_plugin_slug'))->log('2026_01_01_000000_first', 1, '1.0.0'));
  }
}
