<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Issue #85: `bones version X.Y.Z` rewrote readme.txt and the main plugin file, then
 * announced "Version updated to X.Y.Z" — while package.json and package-lock.json kept
 * the previous number. On wp-bannerize-pro the lockfile root had been stale for two
 * releases before anyone looked.
 *
 * The dependency tree must not move: only the plugin's own version entries change.
 */
#[Group('console')]
final class VersionCommandTest extends TestCase
{
  private string $fixture = '';

  protected function setUp(): void
  {
    parent::setUp();

    $root = dirname(__DIR__, 2);
    $this->fixture = sys_get_temp_dir() . '/wpbones-ver-' . bin2hex(random_bytes(6));
    mkdir($this->fixture, 0777, true);

    copy($root . '/src/Console/bin/bones', $this->fixture . '/bones');
    file_put_contents($this->fixture . '/namespace', 'Fixture,Fixture');
    file_put_contents($this->fixture . '/readme.txt', "=== Fixture ===\nStable tag: 1.0.0\n\n== Description ==\n");
    file_put_contents($this->fixture . '/fixture.php', "<?php\n/*\nPlugin Name: Fixture\n * Version: 1.0.0\n*/\n");
    file_put_contents(
      $this->fixture . '/package.json',
      "{\n  \"name\": \"fixture\",\n  \"version\": \"1.0.0\",\n  \"dependencies\": {\n    \"left-pad\": \"1.0.0\"\n  }\n}\n"
    );
    file_put_contents(
      $this->fixture . '/package-lock.json',
      "{\n  \"name\": \"fixture\",\n  \"version\": \"1.0.0\",\n  \"lockfileVersion\": 3,\n  \"packages\": {\n    \"\": {\n      \"name\": \"fixture\",\n      \"version\": \"1.0.0\"\n    },\n    \"node_modules/left-pad\": {\n      \"version\": \"1.0.0\"\n    }\n  }\n}\n"
    );
  }

  protected function tearDown(): void
  {
    if (is_dir($this->fixture)) {
      foreach (glob($this->fixture . '/*') ?: [] as $file) {
        unlink($file);
      }
      rmdir($this->fixture);
    }

    parent::tearDown();
  }

  /**
   * @return array{0:int,1:string}
   */
  private function bones(string ...$arguments): array
  {
    $command = escapeshellarg(PHP_BINARY) . ' bones ' . implode(' ', array_map('escapeshellarg', $arguments));

    $process = proc_open(
      $command . ' 2>&1',
      [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
      $pipes,
      $this->fixture,
      ['PWD' => $this->fixture, 'PATH' => getenv('PATH') ?: '/usr/bin:/bin', 'HOME' => getenv('HOME') ?: '/tmp']
    );

    fwrite($pipes[0], "y\n");   // "the new version will be X, is it ok?"
    fclose($pipes[0]);

    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $output];
  }

  private function read(string $file): string
  {
    return (string) file_get_contents($this->fixture . '/' . $file);
  }

  public function test_it_bumps_every_file_that_declares_the_plugin_version(): void
  {
    [, $output] = $this->bones('version', '1.2.3');

    $this->assertStringContainsString('Stable tag: 1.2.3', $this->read('readme.txt'), $output);
    $this->assertStringContainsString('Version: 1.2.3', $this->read('fixture.php'), $output);
    $this->assertStringContainsString('"version": "1.2.3"', $this->read('package.json'), $output);
    $this->assertStringContainsString('"version": "1.2.3"', $this->read('package-lock.json'), $output);
  }

  public function test_the_lockfile_has_both_root_entries_updated(): void
  {
    $this->bones('version', '1.2.3');

    $lock = json_decode($this->read('package-lock.json'), true);

    $this->assertSame('1.2.3', $lock['version']);
    $this->assertSame('1.2.3', $lock['packages']['']['version']);
  }

  public function test_the_dependency_tree_is_left_alone(): void
  {
    $this->bones('version', '1.2.3');

    $lock = json_decode($this->read('package-lock.json'), true);
    $package = json_decode($this->read('package.json'), true);

    $this->assertSame('1.0.0', $lock['packages']['node_modules/left-pad']['version'], 'a dependency was rewritten');
    $this->assertSame('1.0.0', $package['dependencies']['left-pad'], 'a dependency constraint was rewritten');
  }

  public function test_it_names_the_files_it_changed(): void
  {
    [, $output] = $this->bones('version', '1.2.3');

    $this->assertStringContainsString('package.json', $output);
    $this->assertStringContainsString('package-lock.json', $output);
  }

  public function test_a_plugin_without_a_package_json_still_works(): void
  {
    unlink($this->fixture . '/package.json');
    unlink($this->fixture . '/package-lock.json');

    [$status, $output] = $this->bones('version', '1.2.3');

    $this->assertSame(0, $status, $output);
    $this->assertStringContainsString('Stable tag: 1.2.3', $this->read('readme.txt'));
  }
}
