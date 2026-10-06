<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Foundation\Log\LogServiceProvider;

/**
 * Audit S7 (2026-09-18): with `plugin.logging.type` set to `single` or `daily`, the log went to
 * <plugin>/storage/logs, created 0777, where the web server serves it, messages and JSON context
 * included. Since 3.0 the default folder is under uploads (Support\Storage), and the file name
 * carries a hash keyed with the site's AUTH_SALT, so that it cannot be guessed where .htaccess does
 * not reach. Not wp_hash(), which is pluggable: the plugin boots before pluggable.php loads, and the
 * first live run of this change was a fatal (the stub had hidden it).
 * A `plugin.logging.path` of the plugin's own keeps its folder and its file names.
 */
final class LogServiceProviderTest extends TestCase
{
  private string $uploads;

  private string $errorLog;

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $this->uploads = sys_get_temp_dir() . '/wpbones-uploads-' . bin2hex(random_bytes(6));
    mkdir($this->uploads);

    // error_log() without a destination would write to the test's stderr.
    $this->errorLog = (string) ini_get('error_log');
    ini_set('error_log', $this->uploads . '/php-error.log');

    Functions\when('wp_upload_dir')->alias(fn() => ['basedir' => $this->uploads, 'error' => false]);
    Functions\when('wp_mkdir_p')->alias(fn($dir) => is_dir($dir) || mkdir($dir, 0755, true));
    Functions\when('trailingslashit')->alias(fn($path) => rtrim($path, '/\\') . '/');
    Functions\when('get_site_option')->justReturn('');
  }

  protected function tearDown(): void
  {
    ini_set('error_log', $this->errorLog);

    $entries = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($this->uploads, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($entries as $entry) {
      $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($this->uploads);

    Monkey\tearDown();
    parent::tearDown();
  }

  private function logger(array $config): LogServiceProvider
  {
    $plugin = new class ($config, $this->uploads . '/my-plugin') {
      // Empty, as when the provider is made: the header is read on init.
      public string $slug = '';

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

  private function hash(): string
  {
    $key = defined('AUTH_SALT') && AUTH_SALT ? AUTH_SALT : '';

    return substr(hash_hmac('sha256', 'wpbones-log-my-plugin', $key), 0, 12);
  }

  /**
   * The name is keyed with AUTH_SALT and nothing else: not ABSPATH, which differs between a web
   * request and WP-CLI, and between releases of an atomic deploy (review of #128). In a process of
   * its own, so that the constant does not leak into the other tests.
   */
  #[RunInSeparateProcess]
  #[PreserveGlobalState(false)]
  public function test_the_name_is_keyed_with_auth_salt_only(): void
  {
    define('AUTH_SALT', 'a-salt-for-this-test');

    $this->logger(['plugin.logging.type' => 'single'])->debug('hello');

    $expected = substr(hash_hmac('sha256', 'wpbones-log-my-plugin', 'a-salt-for-this-test'), 0, 12);
    $this->assertFileExists($this->uploads . "/wpbones/my-plugin/logs/debug-{$expected}.log");
  }

  /** wp_hash() is pluggable and not loaded when the plugin boots: the provider must not call it. */
  public function test_the_name_needs_no_pluggable_function(): void
  {
    Functions\expect('wp_hash')->never();

    $this->logger(['plugin.logging.type' => 'single'])->debug('hello');

    $this->assertFileExists($this->uploads . '/wpbones/my-plugin/logs/debug-' . $this->hash() . '.log');
  }

  public function test_a_single_log_goes_under_uploads_with_a_hashed_name(): void
  {
    $this->logger(['plugin.logging.type' => 'single'])->debug('hello');

    $hash = $this->hash();
    $file = $this->uploads . "/wpbones/my-plugin/logs/debug-{$hash}.log";

    $this->assertFileExists($file);
    $this->assertStringContainsString('hello', (string) file_get_contents($file));
    $this->assertDirectoryDoesNotExist($this->uploads . '/my-plugin/storage');
  }

  public function test_a_daily_log_carries_the_date_and_the_hash(): void
  {
    $this->logger(['plugin.logging.type' => 'daily'])->info('hello');

    $hash = $this->hash();

    $this->assertFileExists($this->uploads . '/wpbones/my-plugin/logs/' . date('Y-m-d') . "-{$hash}.log");
  }

  public function test_a_path_of_the_plugins_own_keeps_its_folder_and_names(): void
  {
    $own = $this->uploads . '/my-logs/';

    $this->logger(['plugin.logging.type' => 'single', 'plugin.logging.path' => $own])->debug('hello');

    $this->assertFileExists($own . 'debug.log');
    $this->assertDirectoryDoesNotExist($this->uploads . '/wpbones');
  }

  public function test_without_a_writable_uploads_folder_a_single_log_goes_to_error_log_only(): void
  {
    Functions\when('wp_upload_dir')->justReturn(['basedir' => '', 'error' => 'Unable to create directory']);

    $this->logger(['plugin.logging.type' => 'single'])->debug('hello');

    $this->assertDirectoryDoesNotExist($this->uploads . '/wpbones');
    $this->assertStringContainsString('hello', (string) file_get_contents($this->uploads . '/php-error.log'));
  }

  public function test_errorlog_writes_no_file_and_creates_no_folder(): void
  {
    $this->logger(['plugin.logging.type' => 'errorlog']);

    $this->assertDirectoryDoesNotExist($this->uploads . '/wpbones');
    $this->assertDirectoryDoesNotExist($this->uploads . '/my-plugin/storage');
  }
}
