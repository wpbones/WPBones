<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\PluginStub;
use WPKirk\WPBones\View\View;

/**
 * Audit S6 (2026-09-18): every View created <plugin>/.cache with mkdir(0777) and compiled Blade
 * views there as .bladec files, which the web server served as text. Since 3.0 Blade compiles
 * into the uploads directory (Support\Storage), as .php, and only when a Blade view renders.
 */
final class ViewBladeCacheTest extends TestCase
{
  private PluginStub $plugin;

  private string $uploads;

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $this->uploads = sys_get_temp_dir() . '/wpbones-uploads-' . bin2hex(random_bytes(6));
    mkdir($this->uploads);

    Functions\when('is_admin')->justReturn(true);
    Functions\when('wp_upload_dir')->alias(fn() => ['basedir' => $this->uploads, 'error' => false]);
    Functions\when('wp_mkdir_p')->alias(fn($dir) => is_dir($dir) || mkdir($dir, 0755, true));
    Functions\when('trailingslashit')->alias(fn($path) => rtrim($path, '/\\') . '/');

    $this->plugin = new PluginStub();
    // In a folder, as views are: BladeOne reads a name without a slash as dot notation.
    mkdir($this->plugin->basePath . '/resources/views/pages');
    file_put_contents($this->plugin->basePath . '/resources/views/pages/hello.blade.php', "Hello {{ 'blade' }}");
    file_put_contents($this->plugin->basePath . '/resources/views/plain.php', 'Hello plain');
  }

  protected function tearDown(): void
  {
    $this->plugin->cleanup();
    $this->remove($this->uploads);
    Monkey\tearDown();
    parent::tearDown();
  }

  private function remove(string $dir): void
  {
    $entries = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($entries as $entry) {
      $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($dir);
  }

  public function test_a_blade_view_compiles_into_uploads_as_php(): void
  {
    $html = (new View($this->plugin, 'pages.hello'))->render(true);

    $this->assertSame('Hello blade', trim((string) $html));

    $compiled = glob($this->uploads . '/wpbones/' . basename($this->plugin->basePath) . '/views/*');
    $this->assertNotEmpty(array_filter($compiled, fn($file) => str_ends_with($file, '.php') && basename($file) !== 'index.php'));
    $this->assertSame([], glob($this->uploads . '/wpbones/' . basename($this->plugin->basePath) . '/views/*.bladec'));
  }

  public function test_nothing_is_written_inside_the_plugin_folder(): void
  {
    (new View($this->plugin, 'pages.hello'))->render(true);
    (new View($this->plugin, 'plain'))->render(true);

    $this->assertDirectoryDoesNotExist($this->plugin->basePath . '/.cache');
  }

  public function test_a_blade_view_without_a_writable_uploads_folder_fails_loudly(): void
  {
    Functions\when('wp_upload_dir')->justReturn(['basedir' => '', 'error' => 'Unable to create directory']);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('cannot compile Blade views');

    // render(true) buffers the view, and closes the buffer when it throws.
    (new View($this->plugin, 'pages.hello'))->render(true);
  }

  /** A view that throws with a buffer of its own still open: render(true) restores the level. */
  public function test_render_restores_the_buffer_level_when_a_view_throws_inside_a_buffer(): void
  {
    file_put_contents($this->plugin->basePath . '/resources/views/broken.php', '<?php ob_start(); throw new \\LogicException("broken");');
    $level = ob_get_level();

    try {
      (new View($this->plugin, 'broken'))->render(true);
      $this->fail('The view did not throw.');
    } catch (\LogicException $e) {
      $this->assertSame($level, ob_get_level());
    }
  }

  public function test_a_plain_php_view_creates_no_cache_folder_at_all(): void
  {
    $html = (new View($this->plugin, 'plain'))->render(true);

    // Patchwork (Brain Monkey) rewrites included files: only the start is the view's.
    $this->assertStringStartsWith('Hello plain', (string) $html);
    $this->assertDirectoryDoesNotExist($this->uploads . '/wpbones');
  }
}
