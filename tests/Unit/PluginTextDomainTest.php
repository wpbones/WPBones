<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Foundation\Plugin;

/**
 * `bones rename` rewrites the literal 'wp-kirk' in a plugin's vendor copy to the slug it derives
 * from the plugin name, so the framework loaded the catalogue of that slug: a plugin whose
 * `Text Domain` header differs from its slug (add-browser-search vs wp-abs) never got its own
 * translations. The header is what WordPress itself documents, so the framework reads it.
 */
final class PluginTextDomainTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();
    Functions\when('trailingslashit')->alias(fn($s) => rtrim($s, '/\\') . '/');
  }

  protected function tearDown(): void
  {
    Monkey\tearDown();
    parent::tearDown();
  }

  private function plugin(array $header): Plugin
  {
    $ref = new \ReflectionClass(Plugin::class);
    $plugin = $ref->newInstanceWithoutConstructor();
    $ref->getProperty('basePath')->setValue($plugin, '/var/www/wp-content/plugins/my-plugin');
    $ref->getProperty('pluginData')->setValue($plugin, $header);

    return $plugin;
  }

  public static function headers(): array
  {
    return [
      'the Text Domain header wins' => [
        ['TextDomain' => 'add-browser-search', 'DomainPath' => '/languages'],
        'add-browser-search',
        'my-plugin/languages',
      ],
      'no header: the literal the rename rewrites' => [['DomainPath' => 'languages'], 'wp-kirk', 'my-plugin/languages'],
      'an empty header is no header' => [
        ['TextDomain' => '', 'DomainPath' => 'languages'],
        'wp-kirk',
        'my-plugin/languages',
      ],
      'no Domain Path: languages' => [['TextDomain' => 'abc'], 'abc', 'my-plugin/languages'],
      'an empty Domain Path: languages' => [['TextDomain' => 'abc', 'DomainPath' => ''], 'abc', 'my-plugin/languages'],
      'the leading slash is trimmed' => [
        ['TextDomain' => 'abc', 'DomainPath' => '/lang/files/'],
        'abc',
        'my-plugin/lang/files',
      ],
    ];
  }

  #[DataProvider('headers')]
  public function test_the_catalogue_loaded_is_the_one_the_header_names(
    array $header,
    string $domain,
    string $path
  ): void {
    Functions\expect('load_plugin_textdomain')->once()->with($domain, false, $path);

    $this->plugin($header)->loadTextDomain();
    $this->addToAssertionCount(1);
  }
}
