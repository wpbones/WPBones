<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\PluginStub;
use WPKirk\WPBones\View\Assets\AssetManager;
use WPKirk\WPBones\View\View;

/**
 * Issue #81: withLocalizeScript()/withInlineScript()/withInlineStyle() chose their
 * asset bucket the moment they were called, so a chain that describes the payload
 * before declaring the app sent the payload to the wrong manager. At render time
 * wp_localize_script() then ran before the matching wp_enqueue_script() and
 * WordPress dropped it without a word.
 */
final class ViewAssetRoutingTest extends TestCase
{
  /** WordPress calls the enqueuers make, in order, as "function:handle". */
  private array $calls = [];

  private PluginStub $plugin;

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();
    Functions\when('is_admin')->justReturn(true);

    // Record rather than stub: the order these run in is what the issue is about.
    $this->calls = [];
    foreach (
      [
        'wp_enqueue_script',
        'wp_localize_script',
        'wp_add_inline_script',
        'wp_set_script_translations',
        'wp_enqueue_style',
        'wp_add_inline_style',
      ] as $function
    ) {
      Functions\when($function)->alias(function (...$args) use ($function) {
        $this->calls[] = $function . ':' . (string) $args[0];
      });
    }

    $this->plugin = new PluginStub();
  }

  protected function tearDown(): void
  {
    $this->plugin->cleanup();
    Monkey\tearDown();
    parent::tearDown();
  }

  private function view(): View
  {
    return new View($this->plugin, 'dashboard.index');
  }

  /** Read one of View's protected asset managers. */
  private function bucket(View $view, string $property): AssetManager
  {
    $ref = new \ReflectionProperty(View::class, $property);
    $ref->setAccessible(true);

    return $ref->getValue($view);
  }

  /** Run the protected hook View::render() calls in the admin area. */
  private function enqueue(View $view): void
  {
    foreach (['admin_enqueue_scripts', 'admin_print_styles'] as $method) {
      $ref = new \ReflectionMethod(View::class, $method);
      $ref->setAccessible(true);
      $ref->invoke($view);
    }
  }

  private function handles(array $items, string $key): array
  {
    return array_values(array_map(static fn(array $i) => $i[$key], $items));
  }

  public function test_localize_declared_before_the_app_still_reaches_the_apps_bucket(): void
  {
    $view = $this->view()
      ->withLocalizeScript('app', 'MyPluginData', ['version' => '1.0'])
      ->withAdminAppsScript('app', true);

    $this->enqueue($view);

    $this->assertSame(['app'], $this->handles($this->bucket($view, 'adminAppsAssets')->getLocalizeScripts(), 'handle'));
    $this->assertSame([], $this->bucket($view, 'adminAssets')->getLocalizeScripts());
  }

  public function test_inline_script_declared_before_the_app_still_reaches_the_apps_bucket(): void
  {
    $view = $this->view()
      ->withInlineScript('app', 'window.x = 1;')
      ->withAdminAppsScript('app', true);

    $this->enqueue($view);

    $this->assertSame(['app'], $this->handles($this->bucket($view, 'adminAppsAssets')->getInlineScripts(), 'name'));
    $this->assertSame([], $this->bucket($view, 'adminAssets')->getInlineScripts());
  }

  public function test_inline_style_declared_before_the_app_still_reaches_the_apps_bucket(): void
  {
    $view = $this->view()
      ->withInlineStyle('app', '.x{color:red}')
      ->withAdminAppsScript('app', true);

    $this->enqueue($view);

    $this->assertSame(['app'], $this->handles($this->bucket($view, 'adminAppsAssets')->getInlineStyles(), 'name'));
    $this->assertSame([], $this->bucket($view, 'adminAssets')->getInlineStyles());
  }

  public function test_the_documented_order_keeps_working(): void
  {
    $view = $this->view()
      ->withAdminAppsScript('app', true)
      ->withLocalizeScript('app', 'MyPluginData', ['version' => '1.0'])
      ->withInlineScript('app', 'window.x = 1;');

    $this->enqueue($view);

    $this->assertSame(['app'], $this->handles($this->bucket($view, 'adminAppsAssets')->getLocalizeScripts(), 'handle'));
    $this->assertSame(['app'], $this->handles($this->bucket($view, 'adminAppsAssets')->getInlineScripts(), 'name'));
    $this->assertSame([], $this->bucket($view, 'adminAssets')->getLocalizeScripts());
  }

  public function test_a_plain_admin_script_keeps_its_localize_in_the_admin_bucket(): void
  {
    $view = $this->view()
      ->withLocalizeScript('legacy', 'LegacyData', ['a' => 1])
      ->withAdminScript('legacy');

    $this->enqueue($view);

    $this->assertSame(['legacy'], $this->handles($this->bucket($view, 'adminAssets')->getLocalizeScripts(), 'handle'));
    $this->assertSame([], $this->bucket($view, 'adminAppsAssets')->getLocalizeScripts());
  }

  public function test_an_unknown_handle_falls_back_to_the_admin_bucket(): void
  {
    $view = $this->view()->withLocalizeScript('nobody', 'Data', ['a' => 1]);

    $this->enqueue($view);

    $this->assertSame(['nobody'], $this->handles($this->bucket($view, 'adminAssets')->getLocalizeScripts(), 'handle'));
  }

  public function test_wordpress_sees_the_payload_after_the_script_it_belongs_to(): void
  {
    $view = $this->view()
      ->withLocalizeScript('app', 'MyPluginData', ['version' => '1.0'])
      ->withAdminAppsScript('app', true);

    $this->enqueue($view);

    $enqueued = array_search('wp_enqueue_script:app', $this->calls, true);
    $localized = array_search('wp_localize_script:app', $this->calls, true);
    $trace = implode(', ', $this->calls);

    $this->assertNotFalse($enqueued, 'the app script was never enqueued: ' . $trace);
    $this->assertNotFalse($localized, 'the payload was never localized: ' . $trace);
    $this->assertLessThan(
      $localized,
      $enqueued,
      'wp_localize_script() ran before its script was enqueued, so WordPress drops it: ' . $trace
    );
  }

  public function test_the_frontend_path_is_untouched(): void
  {
    Functions\when('is_admin')->justReturn(false);

    $view = $this->view()
      ->withLocalizeScript('front', 'FrontData', ['a' => 1])
      ->withInlineScript('front', 'window.y = 2;')
      ->withInlineStyle('front', '.y{}');

    $this->assertSame(['front'], $this->handles($this->bucket($view, 'frontendAssets')->getLocalizeScripts(), 'handle'));
    $this->assertSame(['front'], $this->handles($this->bucket($view, 'frontendAssets')->getInlineScripts(), 'name'));
    $this->assertSame(['front'], $this->handles($this->bucket($view, 'frontendAssets')->getInlineStyles(), 'name'));
  }
}
