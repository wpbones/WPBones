<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Foundation\Log\LogServiceProvider;
use WPKirk\WPBones\Tests\Support\PluginStub;
use WPKirk\WPBones\View\View;

/**
 * Audit S6/S7 (2026-09-18), what 2.x can fix without moving anything. The default logger,
 * "errorlog", is documented as error_log() only, yet it also appended to
 * <plugin>/storage/logs/<date>.log; and Blade compiled views into <plugin>/.cache as .bladec.
 * Both folders were made 0777 and both are served as text by the web server (measured on
 * wpbones.test: the log and the compiled PHP of a view came back with a 200).
 *
 * 2.1.3: "errorlog" writes no file and makes no folder; compiled views are .php, so a direct
 * request runs them out of context instead of reading them, and the .bladec files left behind
 * are removed; both folders get an index.php and a deny-all .htaccess, made with wp_mkdir_p().
 * 3.0 moves them out of the plugin folder.
 */
final class GeneratedFilesTest extends TestCase
{
  private PluginStub $plugin;

  private string $errorLog;

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $this->errorLog = (string) ini_get('error_log');
    $this->plugin = new PluginStub();
    ini_set('error_log', $this->plugin->basePath . '/php-error.log');

    Functions\when('is_admin')->justReturn(true);
    Functions\when('wp_mkdir_p')->alias(fn($dir) => is_dir($dir) || mkdir($dir, 0755, true));
  }

  protected function tearDown(): void
  {
    ini_set('error_log', $this->errorLog);
    $this->plugin->cleanup();
    Monkey\tearDown();
    parent::tearDown();
  }

  private function logger(array $config): LogServiceProvider
  {
    $plugin = new class ($config, $this->plugin->basePath) {
      public function __construct(private array $config, public string $basePath)
      {
      }

      public function config($key, $default = null)
      {
        return array_key_exists($key, $this->config) ? $this->config[$key] : $default;
      }
    };

    return new LogServiceProvider($plugin);
  }

  public function test_errorlog_writes_no_file_and_makes_no_folder(): void
  {
    $this->logger(['plugin.logging.type' => 'errorlog'])->debug('hello');

    $this->assertDirectoryDoesNotExist($this->plugin->basePath . '/storage');
    $this->assertStringContainsString('hello', (string) file_get_contents($this->plugin->basePath . '/php-error.log'));
  }

  public function test_errorlog_is_the_default(): void
  {
    $this->logger([])->debug('hello');

    $this->assertDirectoryDoesNotExist($this->plugin->basePath . '/storage');
  }

  public function test_a_single_log_still_writes_storage_logs_and_closes_it(): void
  {
    $this->logger(['plugin.logging.type' => 'single'])->debug('hello');

    $logs = $this->plugin->basePath . '/storage/logs';
    $this->assertStringContainsString('hello', (string) file_get_contents($logs . '/debug.log'));
    $this->assertFileExists($logs . '/index.php');
    $this->assertStringContainsString('Require all denied', (string) file_get_contents($logs . '/.htaccess'));
  }

  public function test_a_blade_view_compiles_to_php_in_a_closed_cache(): void
  {
    mkdir($this->plugin->basePath . '/resources/views/pages');
    file_put_contents($this->plugin->basePath . '/resources/views/pages/hello.blade.php', "Hello {{ 'blade' }}");

    $html = (new View($this->plugin, 'pages.hello'))->render(true);

    $cache = $this->plugin->basePath . '/.cache';
    $this->assertSame('Hello blade', trim((string) $html));
    $this->assertSame([], glob($cache . '/*.bladec'));
    $this->assertCount(1, glob($cache . '/hello.blade.php_*.php'));
    $this->assertFileExists($cache . '/index.php');
    $this->assertStringContainsString('Require all denied', (string) file_get_contents($cache . '/.htaccess'));
  }

  public function test_the_bladec_files_left_by_earlier_versions_are_removed(): void
  {
    mkdir($this->plugin->basePath . '/.cache');
    file_put_contents($this->plugin->basePath . '/.cache/index.blade.php_0123.bladec', '<?php echo "old";');

    new View($this->plugin, 'pages.hello');

    $this->assertSame([], glob($this->plugin->basePath . '/.cache/*.bladec'));
  }
}
