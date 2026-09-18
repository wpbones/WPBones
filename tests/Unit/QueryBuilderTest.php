<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Database\QueryBuilder;
use WPKirk\WPBones\Tests\Support\WpdbSpy;

final class QueryBuilderTest extends TestCase
{
  private WpdbSpy $wpdb;

  protected function setUp(): void
  {
    $this->wpdb = $GLOBALS['wpdb'];
    $this->wpdb->reset();
    $this->wpdb->prefix = 'wp_';
    QueryBuilder::flushDescriptionCache();
  }

  // ---------------------------------------------------------------- table

  public function test_constructor_runs_no_query(): void
  {
    new QueryBuilder('posts');

    $this->assertSame([], $this->wpdb->queries);
  }

  public function test_columns_are_described_once_per_table_per_request(): void
  {
    (new QueryBuilder('posts'))->getColumns();
    (new QueryBuilder('posts'))->getColumns();

    $this->assertSame(['DESC `wp_posts`'], $this->wpdb->queries);
  }

  public function test_flushing_the_description_cache_describes_again(): void
  {
    (new QueryBuilder('posts'))->getColumns();
    QueryBuilder::flushDescriptionCache();
    (new QueryBuilder('posts'))->getColumns();

    $this->assertCount(2, $this->wpdb->queries);
  }

  public function test_constructor_refuses_a_table_name_that_is_not_an_identifier(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid table name');

    new QueryBuilder('posts` WHERE 1; --');
  }

  public function test_set_table_accepts_a_prefixed_name_and_refuses_anything_else(): void
  {
    $builder = new QueryBuilder('posts');
    $builder->setTable('wp_custom_table');
    $this->assertSame('wp_custom_table', $builder->getTable());

    $this->expectException(InvalidArgumentException::class);
    $builder->setTable('wp_x`; DROP TABLE wp_posts; --');
  }

  // ---------------------------------------------------------------- select

  public function test_get_selects_everything_from_the_prefixed_table(): void
  {
    (new QueryBuilder('posts'))->get();

    $this->assertSame('SELECT * FROM `wp_posts` WHERE 1 ', $this->wpdb->last());
  }

  public function test_select_quotes_columns_and_aliases(): void
  {
    (new QueryBuilder('users'))->select('user_login', 'user_email as email')->get();

    $this->assertStringStartsWith('SELECT `user_email` AS `email`,`user_login` FROM `wp_users`', $this->wpdb->last());
  }

  public function test_select_accepts_a_table_qualified_column_and_star(): void
  {
    (new QueryBuilder('users'))->select(['users.ID', 'users.*'])->get();

    $this->assertStringStartsWith('SELECT `users`.*,`users`.`ID` FROM', $this->wpdb->last());
  }

  public function test_select_refuses_an_expression(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid column identifier');

    (new QueryBuilder('users'))->select(['(SELECT user_pass FROM wp_users LIMIT 1) AS p'])->get();
  }

  // ---------------------------------------------------------------- where: shape

  public function test_where_with_two_arguments_defaults_to_equality(): void
  {
    (new QueryBuilder('posts'))->where('ID', 5)->get();

    $this->assertStringContainsString('WHERE 1 AND `ID` = 5 ', $this->wpdb->last());
  }

  public function test_where_quotes_a_table_qualified_column(): void
  {
    (new QueryBuilder('posts'))->where('posts.ID', 5)->get();

    $this->assertStringContainsString('WHERE 1 AND `posts`.`ID` = 5 ', $this->wpdb->last());
  }

  public function test_or_where_joins_with_or(): void
  {
    (new QueryBuilder('posts'))->where('ID', 5)->orWhere('ID', 6)->get();

    $this->assertStringContainsString('WHERE 1 AND `ID` = 5 OR `ID` = 6 ', $this->wpdb->last());
  }

  public function test_where_with_a_null_value_becomes_is_null(): void
  {
    (new QueryBuilder('posts'))->where('post_parent', null)->where('post_password', '<>', null)->get();

    $this->assertStringContainsString('AND `post_parent` IS NULL AND `post_password` IS NOT NULL ', $this->wpdb->last());
  }

  public function test_where_in_with_an_empty_list_can_never_match(): void
  {
    (new QueryBuilder('posts'))->whereIn('ID', [])->whereNotIn('post_type', [])->get();

    $this->assertStringContainsString('WHERE 1 AND 0 = 1 AND 1 = 1 ', $this->wpdb->last());
  }

  public function test_where_between_emits_both_bounds(): void
  {
    (new QueryBuilder('users'))->whereBetween('user_status', [1, 100])->get();

    $this->assertStringContainsString('`user_status` BETWEEN 1 AND 100 ', $this->wpdb->last());
  }

  public function test_where_between_needs_exactly_two_values(): void
  {
    $this->expectException(InvalidArgumentException::class);

    (new QueryBuilder('users'))->whereBetween('user_status', [1, 2, 3])->get();
  }

  public function test_limit_and_offset_are_cast_to_integers(): void
  {
    (new QueryBuilder('posts'))->limit('10abc')->offset('3xyz')->get();

    $this->assertStringEndsWith(' LIMIT 10 OFFSET 3', $this->wpdb->last());
  }

  // ---------------------------------------------------------------- where: values are literals, never SQL

  public function test_where_escapes_a_single_quote_in_the_value(): void
  {
    (new QueryBuilder('posts'))->where('post_author', "O'Reilly")->get();

    $this->assertStringContainsString("`post_author` = 'O\\'Reilly' ", $this->wpdb->last());
  }

  public function test_where_in_quotes_and_escapes_string_values(): void
  {
    (new QueryBuilder('posts'))->whereIn('post_status', ['publish', "x') OR 1=1 -- "])->get();

    $this->assertStringContainsString("`post_status` IN ('publish','x\\') OR 1=1 -- ') ", $this->wpdb->last());
  }

  public function test_numeric_strings_and_booleans_are_emitted_bare(): void
  {
    (new QueryBuilder('posts'))->where('ID', '42')->where('menu_order', '-1.5')->where('sticky', true)->get();

    $this->assertStringContainsString('`ID` = 42 AND `menu_order` = -1.5 AND `sticky` = 1 ', $this->wpdb->last());
  }

  public function test_a_numeric_looking_string_with_extra_characters_is_quoted(): void
  {
    (new QueryBuilder('posts'))->where('post_name', '1e3')->where('guid', ' 5')->get();

    $this->assertStringContainsString("`post_name` = '1e3' AND `guid` = ' 5' ", $this->wpdb->last());
  }

  // ---------------------------------------------------------------- where: identifiers and operators are validated

  public function test_where_refuses_a_column_that_is_not_an_identifier(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid column identifier "ID = 1; DROP TABLE x; --"');

    (new QueryBuilder('posts'))->where('ID = 1; DROP TABLE x; --', 1)->get();
  }

  public function test_where_refuses_an_unknown_operator(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Illegal operator "= 1 OR 1"');

    (new QueryBuilder('posts'))->where('ID', '= 1 OR 1', 1);
  }

  public function test_where_operators_are_case_insensitive(): void
  {
    (new QueryBuilder('users'))->where('display_name', 'LIKE', 'T%')->get();

    $this->assertStringContainsString("`display_name` like 'T%' ", $this->wpdb->last());
  }

  public function test_where_boolean_connector_accepts_and_or_in_any_case(): void
  {
    (new QueryBuilder('posts'))->where('ID', '=', 1, 'OR')->whereIn('post_type', ['page'], 'And')->get();

    $this->assertStringContainsString("WHERE 1 OR `ID` = 1 AND `post_type` IN ('page') ", $this->wpdb->last());
  }

  public function test_where_boolean_connector_refuses_anything_else(): void
  {
    // Found by review: the fourth argument used to be interpolated verbatim, so
    // where('id', '=', 1, 'OR 1=1 #')->delete() would have emptied the table.
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Boolean connector must be "and" or "or"');

    (new QueryBuilder('posts'))->where('ID', '=', 1, 'OR 1=1 #')->delete();
  }

  public function test_order_by_quotes_the_column_and_keeps_the_direction(): void
  {
    (new QueryBuilder('users'))->orderBy('display_name', 'DESC')->orderBy('user_email')->get();

    $this->assertStringEndsWith(' ORDER BY `display_name` desc,`user_email` asc', $this->wpdb->last());
  }

  public function test_order_by_direction_is_restricted_to_asc_or_desc(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Order direction must be "asc" or "desc"');

    (new QueryBuilder('posts'))->orderBy('ID', 'asc; DROP TABLE x');
  }

  // ---------------------------------------------------------------- write side

  public function test_insert_quotes_columns_and_escapes_values(): void
  {
    (new QueryBuilder('posts'))->insert(['post_title' => "It's"]);

    $this->assertSame("INSERT INTO `wp_posts` (`post_title`) VALUES ('It\\'s')", $this->wpdb->last());
  }

  public function test_update_escapes_its_values_like_insert_does(): void
  {
    (new QueryBuilder('posts'))->where('ID', 1)->update(['post_title' => "It's", 'menu_order' => 3]);

    $this->assertSame("UPDATE `wp_posts` SET `post_title` = 'It\\'s',`menu_order` = 3  WHERE 1 AND `ID` = 1 ", $this->wpdb->last());
  }

  public function test_update_refuses_a_column_that_is_not_an_identifier(): void
  {
    $this->expectException(InvalidArgumentException::class);

    (new QueryBuilder('posts'))->where('ID', 1)->update(['post_title` = 1, post_author' => 1]);
  }

  public function test_delete_uses_the_same_where_clause(): void
  {
    (new QueryBuilder('posts'))->where('post_status', 'trash')->delete();

    $this->assertSame("DELETE FROM `wp_posts` WHERE 1 AND `post_status` = 'trash' ", $this->wpdb->last());
  }
}
