<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * `bones deploy` copies vendor/ as it is on disk, and a working copy has Composer's dev tree
 * installed: up to 2.0.6 every require-dev package (PHPUnit, Brain Monkey, ...) went into the
 * package, WordPress.org included. The deploy now runs `composer install --no-dev` in the COPY.
 *
 * The fixture uses two local path packages, both with `autoload.files`: a pruned package whose
 * file is still listed in autoload_files.php is a fatal error on the first request, so the
 * assertion that matters is that the deployed autoloader loads. No network: packagist is off.
 */
#[Group('console')]
final class DeployDevPackagesTest extends TestCase
{
  private string $fixture = '';

  private string $target = '';

  protected function setUp(): void
  {
    parent::setUp();

    if (trim((string) shell_exec('command -v composer 2>/dev/null')) === '') {
      $this->markTestSkipped('composer is not available');
    }

    $root = dirname(__DIR__, 2);
    $this->fixture = sys_get_temp_dir() . '/wpbones-dev-' . bin2hex(random_bytes(6));
    $this->target = $this->fixture . '-out';

    foreach (['runtime', 'devtool'] as $package) {
      mkdir("{$this->fixture}/packages/{$package}", 0777, true);
      file_put_contents(
        "{$this->fixture}/packages/{$package}/composer.json",
        json_encode(['name' => "fixture/{$package}", 'version' => '1.0.0', 'autoload' => ['files' => ["{$package}.php"]]])
      );
      file_put_contents("{$this->fixture}/packages/{$package}/{$package}.php", "<?php\nfunction fixture_{$package}() { return '{$package}'; }\n");
    }

    file_put_contents($this->fixture . '/composer.json', json_encode([
      'name' => 'fixture/plugin',
      'repositories' => [
        ['packagist.org' => false],
        ['type' => 'path', 'url' => 'packages/runtime', 'options' => ['symlink' => false]],
        ['type' => 'path', 'url' => 'packages/devtool', 'options' => ['symlink' => false]],
      ],
      'require' => ['fixture/runtime' => '1.0.0'],
      'require-dev' => ['fixture/devtool' => '1.0.0'],
    ], JSON_PRETTY_PRINT));

    copy($root . '/src/Console/bin/bones', $this->fixture . '/bones');
    file_put_contents($this->fixture . '/fixture.php', "<?php\n/*\nPlugin Name: Fixture\nVersion: 1.0.0\n*/\n");

    $this->composer('install');
  }

  protected function tearDown(): void
  {
    foreach ([$this->fixture, $this->target] as $path) {
      $this->remove($path);
    }

    parent::tearDown();
  }

  public function test_dev_packages_are_removed_from_the_package_and_the_autoloader_still_loads(): void
  {
    [$status, $output] = $this->deploy($this->target, '--no-build');

    $this->assertSame(0, $status, $output);
    $this->assertDirectoryExists($this->target . '/vendor/fixture/runtime');
    $this->assertDirectoryDoesNotExist($this->target . '/vendor/fixture/devtool');
    $this->assertStringNotContainsString('devtool', (string) file_get_contents($this->target . '/vendor/composer/autoload_files.php'));
    $this->assertSame('runtime|no-devtool', $this->loadAutoloader($this->target));
    $this->assertStringContainsString('fixture/devtool', $output);
  }

  public function test_the_source_directory_is_left_alone(): void
  {
    $this->deploy($this->target, '--no-build');

    $this->assertDirectoryExists($this->fixture . '/vendor/fixture/devtool');
    $this->assertSame('runtime|devtool', $this->loadAutoloader($this->fixture));
  }

  public function test_composer_files_added_for_the_pruning_do_not_stay_in_a_plain_package(): void
  {
    $this->deploy($this->target, '--no-build');

    $this->assertFileDoesNotExist($this->target . '/composer.json');
    $this->assertFileDoesNotExist($this->target . '/composer.lock');
  }

  public function test_a_wordpress_org_package_keeps_its_composer_files(): void
  {
    [$status, $output] = $this->deploy($this->target, '--no-build', '--wp');

    $this->assertSame(0, $status, $output);
    $this->assertFileExists($this->target . '/composer.json');
    $this->assertFileExists($this->target . '/composer.lock');
    $this->assertDirectoryDoesNotExist($this->target . '/vendor/fixture/devtool');
  }

  public function test_keep_dev_ships_the_dev_packages(): void
  {
    [$status] = $this->deploy($this->target, '--no-build', '--keep-dev');

    $this->assertSame(0, $status);
    $this->assertDirectoryExists($this->target . '/vendor/fixture/devtool');
  }

  public function test_nothing_runs_when_the_dev_tree_is_not_installed(): void
  {
    $this->composer('install', '--no-dev');

    [$status, $output] = $this->deploy($this->target, '--no-build');

    $this->assertSame(0, $status, $output);
    $this->assertStringNotContainsString('dev package', $output);
    $this->assertSame('runtime|no-devtool', $this->loadAutoloader($this->target));
  }

  public function test_without_a_lockfile_the_deploy_refuses_instead_of_shipping_dev_packages(): void
  {
    unlink($this->fixture . '/composer.lock');

    [$status, $output] = $this->deploy($this->target, '--no-build');

    $this->assertSame(1, $status);
    $this->assertStringContainsString('composer.lock', $output);
  }

  private function loadAutoloader(string $root): string
  {
    $code = 'require ' . var_export($root . '/vendor/autoload.php', true) . ';'
      . 'echo fixture_runtime(), "|", function_exists("fixture_devtool") ? fixture_devtool() : "no-devtool";';

    return (string) shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code) . ' 2>&1');
  }

  private function composer(string ...$arguments): void
  {
    exec('composer ' . implode(' ', array_map('escapeshellarg', $arguments)) . ' --no-interaction --no-progress --quiet --working-dir=' . escapeshellarg($this->fixture) . ' 2>&1', $output, $status);
    $this->assertSame(0, $status, implode("\n", $output));
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
      ['PWD' => $this->fixture, 'PATH' => getenv('PATH') ?: '/usr/bin:/bin', 'HOME' => getenv('HOME') ?: '/tmp', 'COMPOSER_HOME' => getenv('COMPOSER_HOME') ?: (getenv('HOME') ?: '/tmp') . '/.composer']
    );

    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $output];
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
}
