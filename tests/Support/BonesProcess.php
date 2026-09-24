<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Support;

/**
 * A throwaway plugin on disk, and the `bones` CLI run against it as a process.
 *
 * `bones` is a single self-executing file, so it cannot be required: the console tests
 * copy it into a temporary folder next to the few files it reads from the plugin root,
 * and run it with proc_open, feeding stdin and keeping stdout and stderr apart. A run
 * that outlives its timeout is killed and reported, so a CLI that loops forever fails
 * the test instead of hanging the suite.
 *
 * The plugin sits one level down, in `$root/fixture-plugin`, so that a test can put
 * things beside it and prove they survive.
 */
final class BonesProcess
{
  /** The folder that holds the plugin, and whatever a test puts beside it. */
  public string $root;

  /** The plugin folder, where `bones` runs. */
  public string $plugin;

  public function __construct()
  {
    $this->root = sys_get_temp_dir() . '/wpbones-bones-' . bin2hex(random_bytes(6));
    $this->plugin = $this->root . '/fixture-plugin';

    mkdir($this->plugin, 0777, true);

    copy(self::source() . '/src/Console/bin/bones', $this->plugin . '/bones');
    file_put_contents($this->plugin . '/namespace', 'WP Kirk,WPKirk');
    file_put_contents($this->plugin . '/wp-kirk.php', "<?php\n/**\n * Plugin Name: WP Kirk\n * Version: 1.0.0\n */\n");
    file_put_contents($this->plugin . '/readme.txt', "=== WP Kirk ===\nStable tag: 1.0.0\n");
  }

  /** Put the framework's stubs where the `make:*` commands read them. */
  public function withStubs(): self
  {
    $from = self::source() . '/src/Console/stubs';
    $to = $this->plugin . '/vendor/wpbones/wpbones/src/Console/stubs';

    mkdir($to, 0777, true);

    foreach (glob($from . '/*.stub') ?: [] as $stub) {
      copy($stub, $to . '/' . basename($stub));
    }

    return $this;
  }

  /**
   * @param string[] $arguments What follows `php bones`.
   *
   * @return array{status:int, stdout:string, stderr:string, output:string, timedOut:bool}
   */
  public function run(array $arguments, string $stdin = '', int $timeout = 20): array
  {
    $command = escapeshellarg(PHP_BINARY) . ' bones ' . implode(' ', array_map('escapeshellarg', $arguments));

    $process = proc_open(
      $command,
      [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
      $pipes,
      $this->plugin,
      // proc_open inherits the parent environment, so PWD would still name the test
      // runner's directory, and that is what the CLI reads to locate WordPress.
      ['PWD' => $this->plugin, 'PATH' => getenv('PATH') ?: '/usr/bin:/bin', 'HOME' => getenv('HOME') ?: '/tmp']
    );

    fwrite($pipes[0], $stdin);
    fclose($pipes[0]);

    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $stdout = '';
    $stderr = '';
    $timedOut = false;
    $deadline = microtime(true) + $timeout;

    do {
      $stdout .= (string) stream_get_contents($pipes[1]);
      $stderr .= (string) stream_get_contents($pipes[2]);
      $state = proc_get_status($process);

      if ($state['running'] && microtime(true) > $deadline) {
        proc_terminate($process, 9);
        $timedOut = true;
        break;
      }

      if ($state['running']) {
        usleep(20000);
      }
    } while ($state['running']);

    $stdout .= (string) stream_get_contents($pipes[1]);
    $stderr .= (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $closed = proc_close($process);

    // proc_get_status() reports the exit code once, on the call that first sees the
    // process gone; proc_close() then answers -1.
    $status = $timedOut ? -1 : ($state['running'] ? $closed : $state['exitcode']);

    return [
      'status' => $status,
      'stdout' => $stdout,
      'stderr' => $stderr,
      'output' => $stdout . $stderr,
      'timedOut' => $timedOut,
    ];
  }

  /** Delete everything the fixture created, including what a failed run left behind. */
  public function remove(): void
  {
    if (!is_dir($this->root)) {
      return;
    }

    $entries = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($entries as $entry) {
      $entry->isDir() && !$entry->isLink() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($this->root);
  }

  private static function source(): string
  {
    return dirname(__DIR__, 2);
  }
}
