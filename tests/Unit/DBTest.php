<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Database\DB;
use WPKirk\WPBones\Tests\Support\WpdbSpy;

final class DBTest extends TestCase
{
  private WpdbSpy $wpdb;

  protected function setUp(): void
  {
    $this->wpdb = $GLOBALS['wpdb'];
    $this->wpdb->reset();
    $this->wpdb->prefix = 'wp_';
  }

  public function test_table_name_prefixes_a_bare_name(): void
  {
    $this->assertSame('wp_books', DB::getTableName('books'));
  }

  public function test_table_name_does_not_double_an_existing_lowercase_prefix(): void
  {
    $this->assertSame('wp_users', DB::getTableName('wp_users'));
  }

  public function test_table_name_does_not_double_a_prefix_produced_by_the_conversion(): void
  {
    // Found by review: a class named WpBooks always mapped to wp_books, because the
    // prefix check ran after the studly/snake conversion. Keep that.
    $this->assertSame('wp_books', DB::getTableName('WpBooks'));
    $this->assertSame('wp_users', DB::getTableName('Wp_Users'));
    $this->assertSame('wp_my_table', DB::getTableName('WpMyTable'));
  }

  public function test_table_name_can_skip_the_prefix(): void
  {
    $this->assertSame('books', DB::getTableName('Books', false));
    $this->assertSame('wp_users', DB::getTableName('wp_users', false));
  }

  public function test_table_name_uses_the_last_namespace_segment(): void
  {
    $this->assertSame('wp_my_plugin_books', DB::getTableName('WPKirk\\Models\\MyPluginBooks'));
  }

  public function test_table_name_converts_studly_names_as_documented(): void
  {
    // Unchanged, documented behaviour.
    $this->assertSame('wp_w_p_my_table', DB::getTableName('WPMyTable'));
    $this->assertSame('wp_w_p_my_table', DB::getTableName('WP_MyTable'));
    $this->assertSame('w_p_my_table', DB::getTableName('WPMyTable', false));
  }

  public function test_table_name_keeps_an_explicit_name_with_a_mixed_case_prefix(): void
  {
    // Issue #63: the prefix used to go through studly()/snake() together with the name.
    $this->wpdb->prefix = 'qgQezmtYw_';

    $this->assertSame('qgQezmtYw_mytable', DB::getTableName('qgQezmtYw_mytable'));
    $this->assertSame('qgQezmtYw_my_plugin_books', DB::getTableName('WPKirk\\Models\\MyPluginBooks'));
  }

  public function test_assert_table_name_accepts_letters_digits_and_underscores_and_trims(): void
  {
    $this->assertSame('wp_2_posts', DB::assertTableName('wp_2_posts'));
    $this->assertSame('qgQezmtYw_mytable', DB::assertTableName(" qgQezmtYw_mytable\n"));
  }

  public function test_assert_table_name_refuses_anything_else(): void
  {
    foreach (['wp_x`; DROP TABLE wp_posts; --', 'db.table', 'wp-posts', '', 'wp posts'] as $bad) {
      try {
        DB::assertTableName($bad);
      } catch (\InvalidArgumentException $e) {
        $this->assertStringContainsString('Invalid table name', $e->getMessage());
        continue;
      }

      $this->fail(sprintf('"%s" was accepted as a table name', $bad));
    }
  }
}
