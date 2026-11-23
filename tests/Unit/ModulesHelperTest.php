<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\PluginStub;

/**
 * Issue #142: the module name went through rtrim($file, '.php'), which strips a set of
 * characters, so wpbones_modules('shop') required plugin/modules/sho.php.
 */
final class ModulesHelperTest extends TestCase
{
  private PluginStub $plugin;

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $this->plugin = new PluginStub();
    mkdir($this->plugin->basePath . '/plugin/modules', 0777, true);

    foreach (['shop', 'Help', 'graph', 'config'] as $name) {
      file_put_contents($this->plugin->basePath . "/plugin/modules/{$name}.php", "<?php return '{$name}';\n");
    }

    Functions\when('WPKirk')->justReturn($this->plugin);
  }

  protected function tearDown(): void
  {
    $this->plugin->cleanup();
    Monkey\tearDown();
    parent::tearDown();
  }

  public static function moduleNames(): array
  {
    return [
      'ends with p' => ['shop', 'shop'],
      'ends with h' => ['graph', 'graph'],
      'ends with p, with the extension' => ['Help.php', 'Help'],
      'ends with neither' => ['config', 'config'],
      'ends with neither, with the extension' => ['config.php', 'config'],
    ];
  }

  #[DataProvider('moduleNames')]
  public function test_the_module_loaded_is_the_one_named(string $argument, string $loaded): void
  {
    $this->assertSame($loaded, wpbones_modules($argument));
    $this->assertSame($loaded, import($argument));
  }
}
