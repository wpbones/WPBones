<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\BonesProcess;

/**
 * A plugin's own test suites shipped with every deploy unless its deploy.php listed them: the
 * default skip list named no `tests/`, no PHPUnit or Jest configuration, and no Jest test, and
 * since `--wp` keeps `resources/assets`, every `__tests__` folder beside the scripts it tests
 * went to WordPress.org with them. Scotty 2.2.0 shipped four, removed by hand before the SVN
 * commit.
 *
 * Since 3.2.0 the deploy leaves out the PHP suite where PHPUnit looks for it, and the JavaScript
 * tests where the jest.config.js that `migrate:to-v2` writes looks for them.
 */
#[Group('console')]
final class DeployTestSuitesTest extends TestCase
{
  private BonesProcess $bones;

  private string $target = '';

  protected function setUp(): void
  {
    parent::setUp();

    $this->bones = new BonesProcess();
    $this->target = $this->bones->root . '/out';

    foreach (
      [
        'plugin/Http/Controllers/TestController.php' => "<?php\n// runtime code\n",
        'tests/bootstrap.php' => "<?php\n",
        'tests/Unit/ExampleTest.php' => "<?php\n",
        'phpunit.xml' => "<phpunit/>\n",
        'phpunit.xml.dist' => "<phpunit/>\n",
        'phpunit.dist.xml' => "<phpunit/>\n",
        'jest.config.js' => "module.exports = {};\n",
        'jest.config.json' => "{}\n",
        'resources/assets/apps/components/Header.tsx' => "export {};\n",
        'resources/assets/apps/components/__tests__/Header.test.tsx' => "test('x', () => {});\n",
        'resources/assets/apps/components/__tests__/fixtures/data.json' => "{}\n",
        'resources/assets/apps/utils/format.ts' => "export {};\n",
        'resources/assets/apps/utils/format.test.ts' => "test('x', () => {});\n",
        'resources/assets/apps/utils/format.spec.js' => "test('x', () => {});\n",
        'resources/assets/apps/utils/latest.ts' => "export {};\n",
        'resources/assets/apps/utils/__snapshots__/format.test.ts.snap' => "// snapshot\n",
        'resources/assets/apps/utils/__mocks__/api.ts' => "export {};\n",
        '__mocks__/fs.js' => "module.exports = {};\n",
        'vendor/acme/lib/__tests__/lib.test.js' => "test('x', () => {});\n",
        'vendor/acme/lib/functions.php' => "<?php\n",
        // Composer's map of the files it requires on every request; $vendorDir and $baseDir are
        // where the copy sits, as in the real one.
        'vendor/composer/autoload_files.php' => "<?php\n\$vendorDir = dirname(__DIR__);\n\$baseDir = dirname(\$vendorDir);\n\nreturn array(\n  'a1' => \$vendorDir . '/acme/lib/functions.php',\n);\n",
      ]
      as $file => $content
    ) {
      $path = $this->bones->plugin . '/' . $file;

      if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
      }

      file_put_contents($path, $content);
    }
  }

  protected function tearDown(): void
  {
    $this->bones->remove();

    parent::tearDown();
  }

  /** @return array{status:int, output:string} */
  private function deploy(string ...$options): array
  {
    return $this->bones->run(['deploy', $this->target, '--no-build', ...$options]);
  }

  public function test_the_php_suite_does_not_reach_the_package(): void
  {
    $result = $this->deploy();

    $this->assertSame(0, $result['status'], $result['output']);
    $this->assertFileExists($this->target . '/wp-kirk.php', 'the deploy did not run');
    $this->assertDirectoryDoesNotExist($this->target . '/tests');
    $this->assertFileDoesNotExist($this->target . '/phpunit.xml');
    $this->assertFileDoesNotExist($this->target . '/phpunit.xml.dist');
    $this->assertFileDoesNotExist($this->target . '/phpunit.dist.xml');
  }

  public function test_the_javascript_tests_do_not_reach_a_wordpress_org_package(): void
  {
    $result = $this->deploy('--wp');
    $assets = $this->target . '/resources/assets/apps';

    $this->assertSame(0, $result['status'], $result['output']);
    $this->assertFileExists($assets . '/components/Header.tsx', '--wp did not keep resources/assets');
    $this->assertFileDoesNotExist($this->target . '/jest.config.js');
    $this->assertFileDoesNotExist($this->target . '/jest.config.json');
    $this->assertDirectoryDoesNotExist($assets . '/components/__tests__');
    $this->assertFileDoesNotExist($assets . '/utils/format.test.ts');
    $this->assertFileDoesNotExist($assets . '/utils/format.spec.js');
    $this->assertDirectoryDoesNotExist($assets . '/utils/__snapshots__');
    $this->assertDirectoryDoesNotExist($assets . '/utils/__mocks__');
    $this->assertDirectoryDoesNotExist($this->target . '/__mocks__');
  }

  public function test_an_autoloaded_file_the_deploy_left_out_stops_it(): void
  {
    // autoload-dev "files" pointing into tests/: the dev autoloader stays when nothing is pruned
    // (no dev package installed, as here, or --keep-dev), and it would require a missing file
    // on every request.
    file_put_contents($this->bones->plugin . '/tests/helpers.php', "<?php\n");
    file_put_contents(
      $this->bones->plugin . '/vendor/composer/autoload_files.php',
      "<?php\n\$vendorDir = dirname(__DIR__);\n\$baseDir = dirname(\$vendorDir);\n\nreturn array(\n  'a1' => \$vendorDir . '/acme/lib/functions.php',\n  'b2' => \$baseDir . '/tests/helpers.php',\n);\n"
    );

    $result = $this->deploy();

    $this->assertNotSame(0, $result['status'], 'a package that cannot load was reported as deployed');
    $this->assertStringContainsString('tests/helpers.php', $result['output']);
    $this->assertStringNotContainsString('acme/lib/functions.php', $result['output']);
  }

  public function test_an_unreadable_folder_is_not_walked_noisily(): void
  {
    mkdir($this->bones->plugin . '/assets/private', 0777, true);
    chmod($this->bones->plugin . '/assets/private', 0);

    try {
      $result = $this->deploy();
    } finally {
      chmod($this->bones->plugin . '/assets/private', 0755);
    }

    $this->assertSame(0, $result['status'], $result['output']);
    $this->assertStringNotContainsString('Warning', $result['output']);
  }

  public function test_the_code_beside_the_tests_still_ships(): void
  {
    $result = $this->deploy('--wp');

    $this->assertSame(0, $result['status'], $result['output']);
    $this->assertFileExists($this->target . '/plugin/Http/Controllers/TestController.php');
    $this->assertFileExists($this->target . '/resources/assets/apps/utils/format.ts');
    $this->assertFileExists($this->target . '/resources/assets/apps/utils/latest.ts');
  }

  public function test_vendor_is_left_as_composer_installed_it(): void
  {
    $result = $this->deploy();

    $this->assertSame(0, $result['status'], $result['output']);
    $this->assertFileExists($this->target . '/vendor/acme/lib/__tests__/lib.test.js');
  }
}
