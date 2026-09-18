<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Support\Str;

final class StrTest extends TestCase
{
  public function test_studly_and_snake_round_trip(): void
  {
    $this->assertSame('WpMyTable', Str::studly('wp_my_table'));
    $this->assertSame('wp_my_table', Str::snake('WpMyTable'));
  }

  public function test_parse_callback_splits_on_the_at_sign(): void
  {
    $this->assertSame(['DashboardController', 'index'], Str::parseCallback('DashboardController@index'));
  }

  public function test_parse_callback_falls_back_to_the_default_method(): void
  {
    $this->assertSame(['DashboardController', 'index'], Str::parseCallback('DashboardController', 'index'));
  }

  public function test_slug_lowercases_and_joins_with_the_separator(): void
  {
    $this->assertSame('wp-bones-rocks', Str::slug('WP Bones Rocks'));
    $this->assertSame('wp_bones_rocks', Str::slug('WP Bones Rocks', '_'));
  }

  public function test_substr_accepts_a_null_length(): void
  {
    // Issue #82: the parameter is implicitly nullable; PHP 8.4 deprecates that form.
    // Passing null explicitly is the call the fix must keep working.
    $this->assertSame('ones', Str::substr('Bones', 1, null));
    $this->assertSame('on', Str::substr('Bones', 1, 2));
  }
}
