<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Routing\AdminMenuProvider;

/**
 * Audit S9 (2026-09-18): a menu of config/menus.php without a `capability` key asked for `read`,
 * so every logged-in user saw the plugin's menu and opened its pages. Since 3.0 it asks for
 * `manage_options`; a menu meant for everyone says `read`. WordPress enforces it, through
 * add_menu_page() and add_submenu_page().
 */
final class AdminMenuProviderTest extends TestCase
{
  /**
   * The capability each call received, as "menu:<slug>" for add_menu_page() and "item:<slug>"
   * for add_submenu_page(): the first item shares the menu's slug, and one key per slug let it
   * overwrite the menu's (the independent review of #125 hard-coded `read` in add_menu_page()
   * and every test still passed).
   */
  private array $capabilities = [];

  private string $basePath;

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $this->capabilities = [];

    Functions\when('sanitize_title')->alias(fn($title) => strtolower(preg_replace('/[^A-Za-z0-9_]+/', '-', (string) $title)));
    Functions\when('add_menu_page')->alias(function ($pageTitle, $menuTitle, $capability, $slug) {
      $this->capabilities["menu:{$slug}"] = $capability;

      return "toplevel_page_{$slug}";
    });
    Functions\when('add_submenu_page')->alias(function ($parent, $pageTitle, $menuTitle, $capability, $slug) {
      $this->capabilities["item:{$slug}"] = $capability;

      return "{$parent}_page_{$slug}";
    });

    $this->basePath = sys_get_temp_dir() . '/wpbones-tests-' . bin2hex(random_bytes(6));
    mkdir($this->basePath . '/config', 0777, true);
  }

  protected function tearDown(): void
  {
    @unlink($this->basePath . '/config/menus.php');
    @rmdir($this->basePath . '/config');
    @rmdir($this->basePath);

    Monkey\tearDown();
    parent::tearDown();
  }

  private function register(array $menus): void
  {
    file_put_contents($this->basePath . '/config/menus.php', '<?php return ' . var_export($menus, true) . ';');

    $plugin = new class ($this->basePath) {
      public string $images = 'https://example.test/images';

      public function __construct(public string $basePath)
      {
      }

      public function csrfAction(): string
      {
        return 'my_plugin_csrf';
      }

      public function getCallableHook($routes)
      {
        return fn() => 'rendered';
      }
    };

    (new AdminMenuProvider($plugin))->register();
  }

  private function menu(array $menu = []): array
  {
    return $menu + [
      'menu_title' => 'My plugin',
      'items' => [
        ['menu_title' => 'Dashboard', 'route' => ['get' => 'Dashboard\DashboardController@index']],
        'settings' => ['menu_title' => 'Settings', 'route' => ['get' => 'Dashboard\DashboardController@settings']],
      ],
    ];
  }

  public function test_a_menu_without_a_capability_asks_for_manage_options_and_so_do_its_items(): void
  {
    $this->register(['my_plugin' => $this->menu()]);

    $this->assertSame('manage_options', $this->capabilities['menu:my_plugin']);
    $this->assertSame('manage_options', $this->capabilities['item:my_plugin']);
    $this->assertSame(['manage_options'], array_values(array_unique($this->capabilities)));
    $this->assertCount(3, $this->capabilities);
  }

  /** A menu under a post type's: no top-level page, and its items take the menu's default. */
  public function test_items_under_a_post_type_menu_ask_for_manage_options(): void
  {
    $this->register(['edit.php?post_type=book' => $this->menu()]);

    $this->assertArrayNotHasKey('menu:edit.php?post_type=book', $this->capabilities);
    $this->assertSame(['manage_options'], array_values(array_unique($this->capabilities)));
    $this->assertCount(2, $this->capabilities);
  }

  /** Every item is guarded, not only the first, which shares the menu's slug (review of #129). */
  public function test_every_item_gets_the_csrf_guard(): void
  {
    $guarded = [];

    foreach (['my_plugin_page_my_plugin', 'my_plugin_page_wpkirk_settings'] as $hook) {
      Actions\expectAdded("load-{$hook}")->zeroOrMoreTimes()->whenHappen(function ($callback, $priority = 10) use (&$guarded, $hook) {
        if ($priority === PHP_INT_MIN) {
          $guarded[] = $hook;
        }
      });
    }

    $this->register(['my_plugin' => $this->menu()]);

    $this->assertSame(['my_plugin_page_my_plugin', 'my_plugin_page_wpkirk_settings'], $guarded);
  }

  public function test_a_menu_that_declares_read_keeps_it_for_its_items(): void
  {
    $this->register(['my_plugin' => $this->menu(['capability' => 'read'])]);

    $this->assertSame('read', $this->capabilities['menu:my_plugin']);
    $this->assertSame(['read'], array_values(array_unique($this->capabilities)));
  }

  public function test_an_item_keeps_its_own_capability(): void
  {
    $menu = $this->menu(['capability' => 'read']);
    $menu['items']['settings']['capability'] = 'manage_options';

    $this->register(['my_plugin' => $menu]);

    $this->assertSame('read', $this->capabilities['menu:my_plugin']);
    $this->assertSame('read', $this->capabilities['item:my_plugin']);
    $this->assertSame('manage_options', end($this->capabilities));
  }
}
