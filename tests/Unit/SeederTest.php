<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Database\Seeder;
use WPKirk\WPBones\Tests\Support\WpdbSpy;

/**
 * Since 3.0 seed data is a migration (wpbones/WPBones#40). The Seeder class stays only so that a
 * 2.x seeder file still loads: the update that brings a plugin onto 3.0 is run by the 2.x code,
 * which includes database/seeders/*.php.
 */
final class SeederTest extends TestCase
{
  private WpdbSpy $wpdb;

  protected function setUp(): void
  {
    $this->wpdb = $GLOBALS['wpdb'];
    $this->wpdb->reset();
    $this->wpdb->prefix = 'wp_';
  }

  public function test_a_2x_seeder_loads_and_does_nothing(): void
  {
    $seeder = new class extends Seeder {
      protected $tablename = 'books';
      protected $runOnce = true;

      public bool $ran = false;

      public function run()
      {
        $this->ran = true;
        $this->truncate();
        $this->insert("(title) VALUES ('a')");
      }
    };

    $this->assertFalse($seeder->ran);
    $this->assertSame([], $this->wpdb->queries);
  }

  public function test_a_seeder_without_a_table_name_no_longer_throws(): void
  {
    $seeder = new class extends Seeder {
      public function run()
      {
      }
    };

    $this->assertInstanceOf(Seeder::class, $seeder);
  }
}
