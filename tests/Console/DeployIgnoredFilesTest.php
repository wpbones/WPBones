<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Issue #84: the deploy copied the plugin directory filtered only by a hardcoded skip
 * list, so anything sitting there shipped — including files git ignores. A local
 * POST.md went out to WordPress.org twice, invisible to `git status` and absent from
 * the deploy output.
 *
 * The guard that matters here is what is NOT skipped. `vendor/` is gitignored in all 14
 * boilerplates and partly gitignored in the released plugins, so honouring .gitignore
 * literally would ship a plugin with no framework in it. `public/` holds what the build
 * just produced and `storage/` is the runtime directory; neither is described by the
 * repository's ignore rules either.
 */
#[Group('console')]
final class DeployIgnoredFilesTest extends TestCase
{
  private string $fixture = '';

  private string $target = '';

  protected function setUp(): void
  {
    parent::setUp();

    if (!$this->gitAvailable()) {
      $this->markTestSkipped('git is not available');
    }

    $root = dirname(__DIR__, 2);
    $this->fixture = sys_get_temp_dir() . '/wpbones-ign-' . bin2hex(random_bytes(6));
    $this->target = $this->fixture . '-out';

    foreach (['plugin', 'notes', 'vendor/lib', 'public/js', 'storage/logs', 'localization'] as $directory) {
      mkdir($this->fixture . '/' . $directory, 0777, true);
    }

    copy($root . '/src/Console/bin/bones', $this->fixture . '/bones');
    file_put_contents($this->fixture . '/fixture.php', "<?php\n/*\nPlugin Name: Fixture\nVersion: 1.0.0\n*/\n");
    file_put_contents($this->fixture . '/plugin/Keep.php', "<?php\n// tracked\n");

    // Ignored, and none of it belongs in a release.
    file_put_contents($this->fixture . '/POST.md', "announcement draft\n");
    file_put_contents($this->fixture . '/notes/draft.md', "notes\n");
    file_put_contents($this->fixture . '/localization/fixture-it_IT.po~', "editor backup\n");

    // Ignored, and all of it is required at runtime or was just built.
    file_put_contents($this->fixture . '/vendor/lib/runtime.php', "<?php\n// the framework\n");
    file_put_contents($this->fixture . '/public/js/app.js', "// built\n");
    file_put_contents($this->fixture . '/storage/logs/.gitkeep', '');

    file_put_contents(
      $this->fixture . '/.gitignore',
      "POST.md\nnotes/\n*.po~\nvendor/\npublic/\nstorage/\n"
    );

    $this->git('init', '-q');
    $this->git('add', '-A');
    $this->git('-c', 'user.email=t@example.test', '-c', 'user.name=t', 'commit', '-qm', 'fixture');
  }

  protected function tearDown(): void
  {
    foreach ([$this->fixture, $this->target] as $path) {
      $this->remove($path);
    }

    parent::tearDown();
  }

  private function gitAvailable(): bool
  {
    exec('git --version 2>/dev/null', $output, $status);

    return $status === 0;
  }

  private function git(string ...$arguments): void
  {
    $command = 'git -C ' . escapeshellarg($this->fixture) . ' ' . implode(' ', array_map('escapeshellarg', $arguments));
    exec($command . ' 2>/dev/null');
  }

  private function remove(string $path): void
  {
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
   * @return array{0:int,1:string}
   */
  private function deploy(string ...$arguments): array
  {
    $command = escapeshellarg(PHP_BINARY) . ' bones deploy ' . implode(' ', array_map('escapeshellarg', $arguments));

    $process = proc_open(
      $command . ' 2>&1',
      [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
      $pipes,
      $this->fixture,
      ['PWD' => $this->fixture, 'PATH' => getenv('PATH') ?: '/usr/bin:/bin', 'HOME' => getenv('HOME') ?: '/tmp']
    );

    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $output];
  }

  public function test_ignored_files_do_not_reach_the_package(): void
  {
    [$status, $output] = $this->deploy($this->target, '--no-build');

    $this->assertSame(0, $status, $output);
    $this->assertFileDoesNotExist($this->target . '/POST.md');
    $this->assertFileDoesNotExist($this->target . '/notes/draft.md');
    $this->assertFileDoesNotExist($this->target . '/localization/fixture-it_IT.po~');
  }

  public function test_the_payload_directories_are_never_stripped(): void
  {
    [$status, $output] = $this->deploy($this->target, '--no-build');

    $this->assertSame(0, $status, $output);
    $this->assertFileExists($this->target . '/vendor/lib/runtime.php', 'the framework was stripped out of the package');
    $this->assertFileExists($this->target . '/public/js/app.js', 'the build output was stripped out of the package');
    $this->assertDirectoryExists($this->target . '/storage/logs');
    $this->assertFileExists($this->target . '/plugin/Keep.php');
  }

  public function test_the_deploy_says_what_it_left_out(): void
  {
    [, $output] = $this->deploy($this->target, '--no-build');

    $this->assertStringContainsString('POST.md', $output);
  }

  public function test_keep_ignored_turns_the_filter_off(): void
  {
    [$status, $output] = $this->deploy($this->target, '--no-build', '--keep-ignored');

    $this->assertSame(0, $status, $output);
    $this->assertFileExists($this->target . '/POST.md');
    $this->assertFileExists($this->target . '/vendor/lib/runtime.php');
  }

  public function test_a_plugin_outside_git_still_deploys(): void
  {
    $this->remove($this->fixture . '/.git');

    [$status, $output] = $this->deploy($this->target, '--no-build');

    $this->assertSame(0, $status, $output);
    $this->assertFileExists($this->target . '/POST.md', 'nothing to consult, so nothing is filtered');
    $this->assertFileExists($this->target . '/plugin/Keep.php');
  }
}
