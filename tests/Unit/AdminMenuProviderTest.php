<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
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
  /** The capability each add_menu_page() and add_submenu_page() call received, by slug. */
  private array $capabilities = [];

  private string $basePath;

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $this->capabilities = [];

    Functions\when('sanitize_title')->alias(fn($title) => strtolower(preg_replace('/[^A-Za-z0-9_]+/', '-', (string) $title)));
    Functions\when('add_menu_page')->alias(function ($pageTitle, $menuTitle, $capability, $slug) {
      $this->capabilities[$slug] = $capability;

      return "toplevel_page_{$slug}";
    });
    Functions\when('add_submenu_page')->alias(function ($parent, $pageTitle, $menuTitle, $capability, $slug) {
      $this->capabilities[$slug] = $capability;

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

    $this->assertSame(['manage_options'], array_values(array_unique($this->capabilities)));
    $this->assertCount(2, $this->capabilities);
  }

  public function test_a_menu_that_declares_read_keeps_it_for_its_items(): void
  {
    $this->register(['my_plugin' => $this->menu(['capability' => 'read'])]);

    $this->assertSame(['read'], array_values(array_unique($this->capabilities)));
  }

  public function test_an_item_keeps_its_own_capability(): void
  {
    $menu = $this->menu(['capability' => 'read']);
    $menu['items']['settings']['capability'] = 'manage_options';

    $this->register(['my_plugin' => $menu]);

    $this->assertSame('read', $this->capabilities['my_plugin']);
    $this->assertSame('manage_options', end($this->capabilities));
  }
}
