<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Foundation\Log\LogServiceProvider;
use WPKirk\WPBones\Tests\Support\PluginStub;

/**
 * Audit S6/S7 (2026-09-18), what 2.x can fix without moving anything. The default logger,
 * "errorlog", is documented as error_log() only, yet it also appended to
 * <plugin>/storage/logs/<date>.log; and Blade compiled views into <plugin>/.cache as .bladec.
 * Both folders were made 0777 and both are served as text by the web server (measured on
 * wpbones.test: the log and the compiled PHP of a view came back with a 200).
 *
 * 2.1.3: "errorlog" writes no file and makes no folder; compiled views are .php behind an
 * .htaccess. 3.0 moves both folders out of the plugin (StorageTest, ViewBladeCacheTest,
 * LogServiceProviderTest): what is left here is the logger's half that did not move.
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
}
