<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\BonesProcess;

/**
 * Issue #133: every `make:*` command read its stub from the framework and nowhere else, so a
 * team that wanted the generated classes to follow its own conventions could only edit
 * vendor/, which the next `composer update` put back.
 *
 * Since 3.1.0 a stub in the plugin's own `stubs/` folder comes first, `php bones stub:publish`
 * copies the framework's there to start from, and `deploy` leaves them out of the package.
 */
#[Group('console')]
final class StubOverridesTest extends TestCase
{
  private BonesProcess $bones;

  protected function setUp(): void
  {
    parent::setUp();

    $this->bones = (new BonesProcess())->withStubs();
  }

  protected function tearDown(): void
  {
    $this->bones->remove();

    parent::tearDown();
  }

  /** The names of the stubs the framework ships, without `.stub`. */
  private function frameworkStubs(): array
  {
    $names = array_map(fn($file) => basename($file, '.stub'), glob(dirname(__DIR__, 2) . '/src/Console/stubs/*.stub') ?: []);
    sort($names);

    return $names;
  }

  private function publishedStubs(): array
  {
    $names = array_map(fn($file) => basename($file, '.stub'), glob($this->bones->plugin . '/stubs/*.stub') ?: []);
    sort($names);

    return $names;
  }

  private function writePluginStub(string $name, string $content): void
  {
    if (!is_dir($this->bones->plugin . '/stubs')) {
      mkdir($this->bones->plugin . '/stubs');
    }

    file_put_contents($this->bones->plugin . "/stubs/{$name}.stub", $content);
  }

  public function test_a_generator_uses_the_plugins_own_stub_first(): void
  {
    $this->writePluginStub('controller', "<?php\n\nnamespace {Namespace}\\Http\\Controllers{Path};\n\n// team stub\nclass {ClassName}\n{\n}\n");

    $result = $this->bones->run(['make:controller', 'Shop/Cart']);

    $this->assertSame(0, $result['status'], $result['output']);

    $written = (string) file_get_contents($this->bones->plugin . '/plugin/Http/Controllers/Shop/Cart.php');

    $this->assertStringContainsString('// team stub', $written);
    $this->assertStringContainsString('namespace WPKirk\Http\Controllers\Shop;', $written, 'the placeholders were not filled');
    $this->assertStringContainsString('class Cart', $written);
    $this->assertStringContainsString('stubs/controller.stub', $result['output'], 'the run does not say it used the plugin stub');
  }

  public function test_without_a_plugin_stub_the_framework_one_is_used(): void
  {
    $this->writePluginStub('model', "// only the model is overridden\n");

    $result = $this->bones->run(['make:controller', 'Cart']);

    $this->assertSame(0, $result['status'], $result['output']);

    $written = (string) file_get_contents($this->bones->plugin . '/plugin/Http/Controllers/Cart.php');

    $this->assertStringNotContainsString('only the model is overridden', $written);
    $this->assertStringContainsString('class Cart', $written);
    $this->assertStringNotContainsString('stubs/controller.stub', $result['output']);
  }

  public function test_a_stub_found_nowhere_stops_the_command_and_writes_nothing(): void
  {
    unlink($this->bones->plugin . '/vendor/wpbones/wpbones/src/Console/stubs/controller.stub');

    $result = $this->bones->run(['make:controller', 'Cart']);

    $this->assertSame(1, $result['status'], $result['output']);
    $this->assertStringContainsString('controller.stub', $result['output']);
    $this->assertFileDoesNotExist($this->bones->plugin . '/plugin/Http/Controllers/Cart.php');
  }

  public function test_publish_copies_every_framework_stub(): void
  {
    $result = $this->bones->run(['stub:publish']);

    $this->assertSame(0, $result['status'], $result['output']);
    $this->assertSame($this->frameworkStubs(), $this->publishedStubs());

    foreach ($this->frameworkStubs() as $name) {
      $this->assertFileEquals(
        $this->bones->plugin . "/vendor/wpbones/wpbones/src/Console/stubs/{$name}.stub",
        $this->bones->plugin . "/stubs/{$name}.stub",
      );
    }
  }

  public function test_publish_copies_only_the_stubs_it_is_given(): void
  {
    $result = $this->bones->run(['stub:publish', 'controller', 'model.stub']);

    $this->assertSame(0, $result['status'], $result['output']);
    $this->assertSame(['controller', 'model'], $this->publishedStubs(), 'model.stub names the model stub');
  }

  public function test_publish_refuses_an_unknown_stub_and_writes_nothing(): void
  {
    $result = $this->bones->run(['stub:publish', 'controller', 'no-such-stub']);

    $this->assertSame(1, $result['status'], $result['output']);
    $this->assertStringContainsString('no-such-stub', $result['output']);
    $this->assertStringContainsString('controller', $result['output'], 'the error does not list the stubs there are');
    $this->assertSame([], $this->publishedStubs());
  }

  public function test_publish_keeps_a_customised_stub_unless_forced(): void
  {
    $this->writePluginStub('controller', "// customised\n");

    $kept = $this->bones->run(['stub:publish', 'controller', 'model']);

    $this->assertSame(0, $kept['status'], $kept['output']);
    $this->assertStringEqualsFile($this->bones->plugin . '/stubs/controller.stub', "// customised\n");
    $this->assertFileExists($this->bones->plugin . '/stubs/model.stub');
    $this->assertStringContainsString('--force', $kept['output'], 'the run does not say how to replace it');

    $forced = $this->bones->run(['stub:publish', 'controller', '--force']);

    $this->assertSame(0, $forced['status'], $forced['output']);
    $this->assertFileEquals(
      $this->bones->plugin . '/vendor/wpbones/wpbones/src/Console/stubs/controller.stub',
      $this->bones->plugin . '/stubs/controller.stub',
    );
  }

  public function test_the_help_lists_the_command(): void
  {
    $result = $this->bones->run([]);

    $this->assertStringContainsString('stub:publish', $result['output']);
  }

  public function test_deploy_leaves_the_published_stubs_out_and_keeps_the_rest_of_the_folder(): void
  {
    $this->writePluginStub('controller', "// team stub\n");
    file_put_contents($this->bones->plugin . '/stubs/runtime.php', "<?php\n// shipped by the plugin\n");
    $target = $this->bones->root . '/out';

    $result = $this->bones->run(['deploy', $target, '--no-build']);

    $this->assertSame(0, $result['status'], $result['output']);
    $this->assertFileDoesNotExist($target . '/stubs/controller.stub');
    $this->assertFileExists($target . '/stubs/runtime.php', 'a file of the plugin was left out with the stubs');
  }

  public function test_deploy_leaves_out_a_folder_that_holds_only_stubs(): void
  {
    $this->writePluginStub('controller', "// team stub\n");
    $this->writePluginStub('model', "// team stub\n");
    $target = $this->bones->root . '/out';

    $result = $this->bones->run(['deploy', $target, '--no-build']);

    $this->assertSame(0, $result['status'], $result['output']);
    $this->assertFileExists($target . '/wp-kirk.php', 'the deploy did not run');
    $this->assertDirectoryDoesNotExist($target . '/stubs');
  }
}
