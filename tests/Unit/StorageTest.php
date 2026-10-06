<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Support\Storage;

/**
 * Audit S6/S7 (2026-09-18): compiled Blade views and log files were written inside the plugin
 * folder, created 0777, where the web server serves them as text (a compiled view was fetched
 * with curl), and where a read-only plugin folder breaks Blade. Since 3.0 the plugin's generated
 * files go under the uploads directory, wpbones/<plugin folder>/<folder>, closed to the web as far as a
 * file can say so: an index.php in every folder, a deny-all .htaccess at the top.
 */
final class StorageTest extends TestCase
{
  private string $uploads;

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $this->uploads = sys_get_temp_dir() . '/wpbones-uploads-' . bin2hex(random_bytes(6));
    mkdir($this->uploads);

    Functions\when('wp_upload_dir')->alias(fn() => ['basedir' => $this->uploads, 'error' => false]);
    Functions\when('wp_mkdir_p')->alias(fn($dir) => is_dir($dir) || mkdir($dir, 0755, true));
    Functions\when('trailingslashit')->alias(fn($path) => rtrim($path, '/\\') . '/');
    Functions\when('get_temp_dir')->justReturn(sys_get_temp_dir() . '/');
  }

  protected function tearDown(): void
  {
    $this->remove($this->uploads);
    Monkey\tearDown();
    parent::tearDown();
  }

  private function remove(string $dir): void
  {
    if (!is_dir($dir)) {
      return;
    }

    $entries = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($entries as $entry) {
      $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($dir);
  }

  public function test_the_folder_is_under_uploads_by_plugin_folder(): void
  {
    $path = Storage::path('my_plugin_slug', 'views');

    $this->assertSame($this->uploads . '/wpbones/my_plugin_slug/views', $path);
    $this->assertDirectoryExists($path);
  }

  public function test_every_level_has_an_index_and_the_top_denies_the_web(): void
  {
    Storage::path('my_plugin_slug', 'logs');

    foreach (['/wpbones', '/wpbones/my_plugin_slug', '/wpbones/my_plugin_slug/logs'] as $level) {
      $this->assertFileExists($this->uploads . $level . '/index.php', $level);
    }

    $htaccess = (string) file_get_contents($this->uploads . '/wpbones/.htaccess');
    $this->assertStringContainsString('Require all denied', $htaccess);
    $this->assertStringContainsString('Deny from all', $htaccess);
  }

  public function test_an_existing_htaccess_is_left_as_the_site_owner_wrote_it(): void
  {
    mkdir($this->uploads . '/wpbones', 0755, true);
    file_put_contents($this->uploads . '/wpbones/.htaccess', "# mine\nDeny from all\n");

    $this->assertNotNull(Storage::path('my_plugin_slug', 'views'));
    $this->assertSame("# mine\nDeny from all\n", file_get_contents($this->uploads . '/wpbones/.htaccess'));
  }

  /** A slug comes from the plugin's header: it never gets to name a folder outside wpbones/. */
  public function test_a_slug_or_folder_cannot_climb_out(): void
  {
    $path = Storage::path('../../evil/slug', '../views');

    $this->assertStringStartsWith($this->uploads . '/wpbones/', $path);
    $this->assertStringNotContainsString('..', substr($path, strlen($this->uploads)));
  }

  /**
   * No fallback (Codex on #128): a shared temporary folder would let other local users read the
   * logs and plant compiled views.
   */
  public function test_without_an_uploads_directory_there_is_no_folder(): void
  {
    Functions\when('wp_upload_dir')->justReturn(['basedir' => '', 'error' => 'Unable to create directory']);
    Functions\expect('get_temp_dir')->never();

    $this->assertNull(Storage::path('my_plugin_slug', 'views'));
  }

  /** An .htaccess that denies nothing, an empty one left by a full disk for instance, is no rule. */
  public function test_an_htaccess_that_denies_nothing_means_no_folder(): void
  {
    mkdir($this->uploads . '/wpbones', 0755, true);
    file_put_contents($this->uploads . '/wpbones/.htaccess', '');

    $this->assertNull(Storage::path('my_plugin_slug', 'views'));
  }

  /** Without its deny rule the folder is not used: Apache would serve what it holds. */
  public function test_a_deny_rule_that_cannot_be_written_means_no_folder(): void
  {
    mkdir($this->uploads . '/wpbones/my_plugin_slug/views', 0755, true);
    chmod($this->uploads . '/wpbones', 0555);

    try {
      $this->assertNull(Storage::path('my_plugin_slug', 'views'));
    } finally {
      chmod($this->uploads . '/wpbones', 0755);
    }
  }
}
