<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * `bones migrate:to-v2` rewrote package.json with PHP's JSON_PRETTY_PRINT, which indents with four
 * spaces where npm and yarn write two: migrating wp-bannerize-pro turned a scripts/devDependencies
 * change into a diff of every line of the file. It also printed "✅ ✅ Migration to v2 complete.",
 * because success() adds the mark and the message carried one too.
 *
 * Runs the real CLI as a process over a throwaway plugin, answering "y" on stdin. The stubs are
 * read from vendor/wpbones/wpbones/src/Console/stubs, so the fixture links that path to this repo.
 */
#[Group('console')]
final class MigrateToV2Test extends TestCase
{
  private string $fixture = '';

  protected function setUp(): void
  {
    parent::setUp();

    // BONES_SOURCE: another WPBones tree, to show a test failing on a previous release (BonesProcess).
    $root = getenv('BONES_SOURCE') ?: dirname(__DIR__, 2);
    $this->fixture = sys_get_temp_dir() . '/wpbones-migrate-' . bin2hex(random_bytes(6));

    mkdir($this->fixture . '/vendor/wpbones/wpbones/src/Console', 0777, true);
    symlink($root . '/src/Console/stubs', $this->fixture . '/vendor/wpbones/wpbones/src/Console/stubs');
    copy($root . '/src/Console/bin/bones', $this->fixture . '/bones');
    file_put_contents($this->fixture . '/fixture.php', "<?php\n/*\nPlugin Name: Fixture\nVersion: 1.0.0\n*/\n");
    file_put_contents($this->fixture . '/gulpfile.js', "// gulp\n");
    // Written the way npm writes it: two-space indentation, non-ASCII left as is.
    file_put_contents($this->fixture . '/package.json', <<<'JSON'
{
  "name": "fixture",
  "version": "1.0.0",
  "author": "José Müller",
  "scripts": {
    "build": "run-s build:gulp"
  },
  "devDependencies": {
    "gulp": "^4.0.2"
  }
}

JSON);
  }

  protected function tearDown(): void
  {
    $entries = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($this->fixture, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($entries as $entry) {
      $entry->isDir() && !$entry->isLink() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($this->fixture);

    parent::tearDown();
  }

  /**
   * @return array{0:int,1:string} exit status and combined output
   */
  private function migrate(): array
  {
    $process = proc_open(
      escapeshellarg(PHP_BINARY) . ' bones migrate:to-v2 2>&1',
      [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
      $pipes,
      $this->fixture,
      ['PWD' => $this->fixture, 'PATH' => getenv('PATH') ?: '/usr/bin:/bin', 'HOME' => getenv('HOME') ?: '/tmp']
    );

    fwrite($pipes[0], "y\n");
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $output];
  }

  public function test_package_json_keeps_the_two_space_indentation_npm_writes(): void
  {
    [$status, $output] = $this->migrate();

    $this->assertSame(0, $status, $output);
    $json = (string) file_get_contents($this->fixture . '/package.json');

    $this->assertStringStartsWith("{\n  \"name\": \"fixture\",\n", $json);
    $this->assertStringContainsString("\n    \"build\": \"wp-scripts build\",\n", $json, 'nested keys are indented by four, not eight');
    $this->assertDoesNotMatchRegularExpression('/^(?:  )* \S/m', $json, 'a line is indented by an odd number of spaces');
    $this->assertStringContainsString('"author": "José Müller"', $json, 'non-ASCII was escaped');
    $this->assertSame('wp-scripts build', json_decode($json, true)['scripts']['build']);
    $this->assertFileDoesNotExist($this->fixture . '/gulpfile.js');
  }

  /**
   * `wp-scripts format` drops --check and always passes --write, so up to 2.0.12 the migrated
   * `format:check` rewrote the plugin, compiled bundles included, and exited 0.
   *
   * This checks the configuration the migration writes. Running it needs node_modules, which this
   * suite does not install: the scripts were run with yarn on a migrated boilerplate (check exit 1
   * on an unformatted file and nothing written, `format` leaving public/ alone) and with pnpm 12.
   */
  public function test_format_check_is_a_prettier_check_and_the_ignore_file_covers_the_build(): void
  {
    [$status, $output] = $this->migrate();

    $this->assertSame(0, $status, $output);
    $scripts = json_decode((string) file_get_contents($this->fixture . '/package.json'), true)['scripts'];

    $this->assertStringStartsWith('prettier --check ', $scripts['format:check']);
    $this->assertStringNotContainsString('--write', $scripts['format:check']);
    $this->assertStringNotContainsString('wp-scripts format', $scripts['format:check']);
    $this->assertStringContainsString('--ignore-path .prettierignore', $scripts['format:check']);
    $this->assertSame('wp-scripts format', $scripts['format']);

    // A direct dependency, or pnpm 12 has no `prettier` binary for the script to run.
    $devDependencies = json_decode((string) file_get_contents($this->fixture . '/package.json'), true)['devDependencies'];
    $this->assertSame('npm:wp-prettier@3.0.3', $devDependencies['prettier'] ?? null);

    $ignore = (string) file_get_contents($this->fixture . '/.prettierignore');
    $this->assertMatchesRegularExpression('#^public/$#m', $ignore, 'format would rewrite the compiled bundles');
    $this->assertMatchesRegularExpression('#^vendor/$#m', $ignore);
  }

  public function test_the_success_line_carries_one_check_mark(): void
  {
    [, $output] = $this->migrate();

    $this->assertStringContainsString('✅ Migration to v2 complete.', $output);
    $this->assertStringNotContainsString('✅ ✅', $output);
  }
}
