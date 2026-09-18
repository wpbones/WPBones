<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Database\DB;
use WPKirk\WPBones\Database\QueryBuilder;
use WPKirk\WPBones\Database\Support\Model;
use WPKirk\WPBones\Tests\Support\WpdbSpy;

/**
 * Support\Model is the row object a query returns: save() and delete() build a fresh
 * QueryBuilder for the row's own table.
 */
final class SupportModelTest extends TestCase
{
  private WpdbSpy $wpdb;

  protected function setUp(): void
  {
    $this->wpdb = $GLOBALS['wpdb'];
    $this->wpdb->reset();
    $this->wpdb->prefix = 'wp_';
    QueryBuilder::flushDescriptionCache();
  }

  /** A row as DB::table()/tableWithoutPrefix() would hand it out: its builder knows its parent. */
  private function row(array $attributes, string $table, bool $usePrefix): Model
  {
    $builder = new QueryBuilder($table, 'id', $usePrefix);
    $builder->setParentModel(new DB($table, 'id', $usePrefix));
    $this->wpdb->reset();

    return new Model($attributes, $builder);
  }

  public function test_delete_targets_the_prefixed_table_of_a_prefixed_builder(): void
  {
    $this->row(['id' => 3, 'title' => 'x'], 'books', true)->delete();

    $this->assertSame(['DELETE FROM `wp_books` WHERE `id` = 3 '], $this->wpdb->queries);
  }

  public function test_delete_keeps_an_unprefixed_table_unprefixed(): void
  {
    // Found during the #87 review: the fresh builder pushed the final name back
    // through the prefix logic, so a row from DB::tableWithoutPrefix() was deleted
    // from wp_<table> instead of <table>.
    $this->row(['id' => 3, 'title' => 'x'], 'my_plugin_books', false)->delete();

    $this->assertSame(['DELETE FROM `my_plugin_books` WHERE `id` = 3 '], $this->wpdb->queries);
  }

  public function test_save_updates_the_row_in_its_own_table(): void
  {
    $row = $this->row(['id' => 3, 'title' => 'x'], 'my_plugin_books', false);
    $row->title = "It's";

    $row->save();

    $this->assertSame(
      ["UPDATE `my_plugin_books` SET `id` = 3,`title` = 'It\\'s'  WHERE `id` = 3 "],
      $this->wpdb->queries
    );
  }

  public function test_delete_keeps_a_mixed_case_prefix_verbatim(): void
  {
    $this->wpdb->prefix = 'qgQezmtYw_';

    $this->row(['id' => 7], 'books', true)->delete();

    $this->assertSame(['DELETE FROM `qgQezmtYw_books` WHERE `id` = 7 '], $this->wpdb->queries);
  }
}
