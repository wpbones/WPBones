<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Issue #83: `bones deploy` ran the build with shell_exec(), which throws the exit
 * status away. A failed build printed "Build completed", the deploy packaged whatever
 * happened to be in public/ and the command exited 0 — a broken release is
 * indistinguishable from a good one.
 *
 * These run the real CLI as a process. `bones` is a single self-executing file, so it
 * cannot be required; and it needs no WordPress — loadWordPress() returns quietly when
 * wp-load.php is not there, which is what makes a throwaway fixture enough.
 *
 * The package manager is `false` and `true`: two POSIX utilities that exit 1 and 0
 * without running anything, so the test needs neither node nor npm.
 */
#[Group('console')]
final class DeployBuildTest extends TestCase
{
  private string $fixture = '';

  private string $target = '';

  protected function setUp(): void
  {
    parent::setUp();

    $root = dirname(__DIR__, 2);
    $this->fixture = sys_get_temp_dir() . '/wpbones-cli-' . bin2hex(random_bytes(6));
    $this->target = $this->fixture . '-out';

    mkdir($this->fixture . '/public', 0777, true);
    copy($root . '/src/Console/bin/bones', $this->fixture . '/bones');
    file_put_contents(
      $this->fixture . '/package.json',
      json_encode(['name' => 'fixture', 'scripts' => ['build' => 'exit 3']], JSON_PRETTY_PRINT)
    );
    file_put_contents($this->fixture . '/fixture.php', "<?php\n/*\nPlugin Name: Fixture\nVersion: 1.0.0\n*/\n");
    file_put_contents($this->fixture . '/public/app.js', "// built\n");
  }

  protected function tearDown(): void
  {
    foreach ([$this->fixture, $this->target] as $path) {
      $this->remove($path);
    }
    $this->remove($this->target . '.zip');
    $this->remove($this->fixture . '-out-tmp');

    parent::tearDown();
  }

  private function remove(string $path): void
  {
    if (is_file($path)) {
      unlink($path);

      return;
    }

    if (!is_dir($path)) {
      return;
    }

    $entries = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($entries as $entry) {
      $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($path);
  }

  /**
   * @return array{0:int,1:string} exit status and combined output
   */
  private function deploy(string ...$arguments): array
  {
    $command = escapeshellarg(PHP_BINARY) . ' bones deploy ' . implode(' ', array_map('escapeshellarg', $arguments));

    $process = proc_open(
      $command . ' 2>&1',
      [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
      $pipes,
      $this->fixture,
      // proc_open inherits the parent environment, so PWD would still name the test
      // runner's directory — and that is what the CLI reads to locate WordPress.
      ['PWD' => $this->fixture, 'PATH' => getenv('PATH') ?: '/usr/bin:/bin', 'HOME' => getenv('HOME') ?: '/tmp']
    );

    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $output];
  }

  public function test_a_failing_build_stops_the_deploy(): void
  {
    [$status, $output] = $this->deploy($this->target, '--pkgm=false', '--create-zip');

    $this->assertNotSame(0, $status, "the deploy exited 0 after a failed build:\n" . $output);
    $this->assertStringNotContainsString('Build completed', $output);
    $this->assertFileDoesNotExist($this->target . '.zip', 'a package was produced from a failed build');
  }

  public function test_the_failure_says_what_failed(): void
  {
    [, $output] = $this->deploy($this->target, '--pkgm=false', '--create-zip');

    $this->assertStringContainsString("Build failed: 'false run build' exited with status 1.", $output);
    $this->assertStringContainsString('Deploy aborted', $output);
  }

  public function test_a_successful_build_still_packages(): void
  {
    [$status, $output] = $this->deploy($this->target, '--pkgm=true', '--create-zip');

    $this->assertSame(0, $status, $output);
    $this->assertStringContainsString('Build completed', $output);
    $this->assertFileExists($this->target . '.zip');
  }

  public function test_no_build_skips_the_build_entirely(): void
  {
    [$status, $output] = $this->deploy($this->target, '--pkgm=false', '--create-zip', '--no-build');

    $this->assertSame(0, $status, $output);
    $this->assertStringNotContainsString('run build', $output);
    $this->assertFileExists($this->target . '.zip');
  }
}
