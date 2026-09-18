<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
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
  }

  public function test_constructor_describes_the_table(): void
  {
    new QueryBuilder('posts');

    // Documents current behaviour: one DESC round trip per instantiation (audit P2).
    $this->assertSame(['DESC `wp_posts`'], $this->wpdb->queries);
  }

  public function test_get_selects_everything_from_the_prefixed_table(): void
  {
    (new QueryBuilder('posts'))->get();

    $this->assertSame('SELECT * FROM `wp_posts` WHERE 1 ', $this->wpdb->last());
  }

  public function test_where_with_two_arguments_defaults_to_equality(): void
  {
    (new QueryBuilder('posts'))->where('ID', 5)->get();

    $this->assertStringContainsString('WHERE 1 AND ID = 5 ', $this->wpdb->last());
  }

  public function test_limit_and_offset_are_cast_to_integers(): void
  {
    (new QueryBuilder('posts'))->limit('10abc')->offset('3xyz')->get();

    $this->assertStringEndsWith(' LIMIT 10 OFFSET 3', $this->wpdb->last());
  }

  public function test_insert_escapes_its_values(): void
  {
    (new QueryBuilder('posts'))->insert(['post_title' => "It's"]);

    $this->assertSame("INSERT INTO `wp_posts` (post_title) VALUES ('It\\'s')", $this->wpdb->last());
  }

  #[Group('known-defect')]
  public function test_where_escapes_a_single_quote_in_the_value(): void
  {
    // Audit S1: getFormatValue() wraps the raw value in quotes with no escaping.
    (new QueryBuilder('posts'))->where('post_author', "O'Reilly")->get();

    $this->assertStringContainsString("post_author = 'O\\'Reilly'", $this->wpdb->last());
  }

  #[Group('known-defect')]
  public function test_where_in_quotes_and_escapes_string_values(): void
  {
    // Audit S1: whereIn() implodes the raw values, neither quoted nor escaped.
    (new QueryBuilder('posts'))->whereIn('post_status', ['publish', "x') OR 1=1 -- "])->get();

    $this->assertStringContainsString("post_status IN ('publish','x\\') OR 1=1 -- ')", $this->wpdb->last());
  }

  #[Group('known-defect')]
  public function test_order_by_direction_is_restricted_to_asc_or_desc(): void
  {
    // Audit S1: orderBy() copies the direction verbatim.
    (new QueryBuilder('posts'))->orderBy('ID', 'asc; DROP TABLE x')->get();

    $this->assertMatchesRegularExpression('/ORDER BY ID (asc|desc)$/i', rtrim($this->wpdb->last()));
  }

  #[Group('known-defect')]
  public function test_update_escapes_its_values_like_insert_does(): void
  {
    // Audit S1: update() interpolates "'$value'" while insert() goes through _real_escape.
    (new QueryBuilder('posts'))->where('ID', 1)->update(['post_title' => "It's"]);

    $this->assertStringContainsString("SET `post_title` = 'It\\'s'", $this->wpdb->last());
  }
}
