<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\BonesProcess;

/**
 * `bones migrate:to-v3` lists what 3.0 closes (audit S2, S3, S9): the pages, menus and REST routes
 * that declare no capability or permission_callback, which 2.x opened to every logged-in user, or to
 * everyone, and 3.0 gives to administrators only. It names them and changes nothing: who may open
 * a page is the author's decision, and these files are arbitrary PHP.
 */
#[Group('console')]
final class MigrateToV3AccessTest extends TestCase
{
  private BonesProcess $bones;

  protected function setUp(): void
  {
    parent::setUp();

    $this->bones = new BonesProcess();
  }

  protected function tearDown(): void
  {
    $this->bones->remove();
    parent::tearDown();
  }

  private function put(string $path, string $code): void
  {
    $file = $this->bones->plugin . '/' . $path;

    if (!is_dir(dirname($file))) {
      mkdir(dirname($file), 0777, true);
    }

    file_put_contents($file, $code);
  }

  private function convert(): array
  {
    return $this->bones->run(['migrate:to-v3'], "y\n");
  }

  public function test_route_pages_without_a_capability_are_listed(): void
  {
    $this->put('config/routes.php', <<<'PHP'
      <?php
      if (!defined('ABSPATH')) {
        exit();
      }

      return [
        'open_page' => [
          'title' => __('Open', 'wp-kirk'),
          'route' => ['get' => 'Dashboard\DashboardController@open'],
        ],
        'admin_page' => [
          'title' => __('Admin', 'wp-kirk'),
          'capability' => 'manage_options',
          'route' => ['get' => 'Dashboard\DashboardController@admin'],
        ],
        'read_page' => array(
          'title' => 'Read',
          'capability' => 'read',
          'route' => array('get' => 'Dashboard\DashboardController@read'),
        ),
      ];
      PHP);
    $before = file_get_contents($this->bones->plugin . '/config/routes.php');

    $run = $this->convert();

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString("config/routes.php: the page open_page declares no capability", $run['output']);
    $this->assertStringNotContainsString('admin_page', $run['output']);
    $this->assertStringNotContainsString('read_page', $run['output']);
    $this->assertSame($before, file_get_contents($this->bones->plugin . '/config/routes.php'));
  }

  /** A menu's capability covers its items: an item without one is not listed on its own. */
  public function test_menus_without_a_capability_are_listed(): void
  {
    $this->put('config/menus.php', <<<'PHP'
      <?php
      return [
        'my_plugin_slug_menu' => [
          'menu_title' => 'My plugin',
          'items' => [
            ['menu_title' => 'Dashboard', 'capability' => 'read', 'route' => ['get' => 'A@b']],
          ],
        ],
        'my_other_menu' => [
          'menu_title' => 'Other',
          'capability' => 'read',
          'items' => [
            ['menu_title' => 'Settings', 'route' => ['get' => 'A@c']],
          ],
        ],
      ];
      PHP);

    $run = $this->convert();

    $this->assertStringContainsString('config/menus.php: the menu my_plugin_slug_menu declares no capability', $run['output']);
    $this->assertStringNotContainsString('my_other_menu', $run['output']);
  }

  public function test_pages_folder_classes_without_a_capability_method_are_listed(): void
  {
    $this->put('pages/OpenPage.php', "<?php\nclass OpenPage extends Page { public function title() { return 'x'; } public function render() { return ''; } }\n");
    $this->put('pages/AdminPage.php', "<?php\nclass AdminPage extends Page { public function title() { return 'x'; }\n public function capability(): string { return 'read'; }\n public function render() { return ''; } }\n");

    $run = $this->convert();

    $this->assertStringContainsString('pages/OpenPage.php declares no capability()', $run['output']);
    $this->assertStringNotContainsString('pages/AdminPage.php', $run['output']);
  }

  public function test_rest_routes_without_a_permission_callback_are_listed_with_their_line(): void
  {
    $this->put('api/vendor/v1/route.php', <<<'PHP'
      <?php
      use WPKirk\WPBones\Routing\API\Route;

      Route::get('/open', function () {
        return 'Hello World!';
      });

      Route::get('/public', fn() => 'hi', ['permission_callback' => '__return_true']);

      Route::post('/closed', '\WPKirk\API\Controller@store', [
        'permission_callback' => fn() => current_user_can('manage_options'),
      ]);

      Route::request(['get', 'post'], '/multiple', '\WPKirk\API\Controller@multiple');
      PHP);
    $before = file_get_contents($this->bones->plugin . '/api/vendor/v1/route.php');

    $run = $this->convert();

    $this->assertStringContainsString("api/vendor/v1/route.php:4: Route::get('/open') has no permission_callback", $run['output']);
    $this->assertStringContainsString("api/vendor/v1/route.php:14: Route::request('/multiple') has no permission_callback", $run['output']);
    $this->assertStringNotContainsString("'/public'", $run['output']);
    $this->assertStringNotContainsString("'/closed'", $run['output']);
    $this->assertSame($before, file_get_contents($this->bones->plugin . '/api/vendor/v1/route.php'));
  }

  /** RestProvider reads its folder from api.custom.path (Codex, round 1 on #125). */
  public function test_the_rest_folder_named_in_config_api_is_the_one_scanned(): void
  {
    $this->put('config/api.php', "<?php\nreturn ['custom' => ['path' => '/routes-api', 'enabled' => true]];\n");
    $this->put('routes-api/vendor/v1/route.php', "<?php\nRoute::get('/elsewhere', fn() => 1);\n");
    $this->put('api/vendor/v1/route.php', "<?php\nRoute::get('/not-loaded', fn() => 1);\n");

    $run = $this->convert();

    $this->assertStringContainsString("routes-api/vendor/v1/route.php:2: Route::get('/elsewhere') has no permission_callback", $run['output']);
    $this->assertStringNotContainsString('/not-loaded', $run['output']);
  }

  /** Only custom.path names the folder, and only a whole literal does (Codex, round 2 on #125). */
  public function test_only_a_literal_custom_path_names_the_folder(): void
  {
    $this->put('config/api.php', "<?php\nreturn ['wp' => ['path' => '/unused'], 'custom' => ['path' => '/routes-api']];\n");
    $this->put('routes-api/v/v1/route.php', "<?php\nRoute::get('/here', fn() => 1);\n");
    $this->put('unused/v/v1/route.php', "<?php\nRoute::get('/not-here', fn() => 1);\n");

    $run = $this->convert();

    $this->assertStringContainsString("routes-api/v/v1/route.php:2: Route::get('/here')", $run['output']);
    $this->assertStringNotContainsString('/not-here', $run['output']);

    $this->put('config/api.php', "<?php\nreturn ['custom' => ['path' => '/routes' . '-api']];\n");

    $run = $this->convert();

    $this->assertStringContainsString('config/api.php: the REST route folder is not a literal path', $run['output']);
  }

  /** A config the tokens cannot read is a review item, never "nothing to change" (Codex, round 1). */
  public function test_a_config_that_does_not_return_a_literal_array_is_flagged(): void
  {
    $this->put('config/routes.php', "<?php\n\$pages = ['p' => ['title' => 'P', 'route' => ['get' => 'A@b']]];\nreturn \$pages;\n");

    $run = $this->convert();

    $this->assertStringContainsString('config/routes.php does not return a literal array', $run['output']);
    $this->assertStringNotContainsString('Nothing to change', $run['output']);
  }

  /** Only the options argument counts: a callback that mentions the key is not a declaration. */
  public function test_permission_callback_counts_only_as_a_key_of_the_options(): void
  {
    $this->put('api/vendor/v1/route.php', <<<'PHP'
      <?php
      Route::get('/mentions', function () {
        return ['permission_callback' => 'none of my business'];
      });
      Route::get('/variable', fn() => 1, $options);
      PHP);

    $run = $this->convert();

    $this->assertStringContainsString("route.php:2: Route::get('/mentions') has no permission_callback", $run['output']);
    $this->assertStringContainsString("route.php:5: Route::get('/variable') passes options that are not a literal array", $run['output']);
  }

  /**
   * From the independent review of #125: each of these made the report say "Nothing to change".
   */
  public function test_a_key_that_is_not_a_literal_makes_the_config_unreadable(): void
  {
    $this->put('config/routes.php', <<<'PHP'
      <?php
      return [
        'open_page' => ['title' => 'Open', 'route' => ['get' => 'A@b']],
        Pages::ADMIN => ['title' => 'Admin', 'capability' => 'manage_options', 'route' => ['get' => 'A@c']],
      ];
      PHP);

    $run = $this->convert();

    $this->assertStringContainsString('config/routes.php does not return a literal array', $run['output']);
  }

  public function test_an_early_return_inside_a_block_is_not_the_config(): void
  {
    $this->put('config/routes.php', <<<'PHP'
      <?php
      if (!defined('ABSPATH')) {
        return [];
      }

      return [
        'open_page' => ['title' => 'Open', 'route' => ['get' => 'A@b']],
      ];
      PHP);

    $run = $this->convert();

    $this->assertStringContainsString('config/routes.php: the page open_page declares no capability', $run['output']);
  }

  public function test_an_empty_capability_is_reported_like_a_missing_one(): void
  {
    $this->put('config/routes.php', "<?php\nreturn [\n  'a' => ['title' => 'A', 'capability' => null, 'route' => ['get' => 'A@b']],\n  'b' => ['title' => 'B', 'capability' => '', 'route' => ['get' => 'A@c']],\n];\n");
    $this->put('api/v/v1/route.php', "<?php\nRoute::get('/null', fn() => 1, ['permission_callback' => null]);\n");

    $run = $this->convert();

    $this->assertStringContainsString('the page a declares no capability', $run['output']);
    $this->assertStringContainsString('the page b declares no capability', $run['output']);
    $this->assertStringContainsString("Route::get('/null') has no permission_callback", $run['output']);
  }

  public function test_an_aliased_lowercase_or_nested_route_is_read_too(): void
  {
    $this->put('api/v/v1/route.php', <<<'PHP'
      <?php
      use WPKirk\WPBones\Routing\API\Route as Api;

      Api::post('/aliased', fn() => 1);
      route::get('/lowercase', fn() => 1);
      Route::get('/outer', function () {
        Route::get('/inner', fn() => 1);
      }, ['permission_callback' => '__return_true']);
      Route::get('/attribute', #[Pure] fn() => 1, ['permission_callback' => '__return_true']);
      PHP);

    $run = $this->convert();

    $this->assertStringContainsString("Route::post('/aliased') has no permission_callback", $run['output']);
    $this->assertStringContainsString("Route::get('/lowercase') has no permission_callback", $run['output']);
    $this->assertStringContainsString("Route::get('/inner') has no permission_callback", $run['output']);
    $this->assertStringNotContainsString("'/outer'", $run['output']);
    $this->assertStringNotContainsString("'/attribute'", $run['output']);
  }

  public function test_a_pages_capability_method_is_read_from_the_tokens(): void
  {
    $this->put('pages/Commented.php', "<?php\nclass Commented extends Page {\n  // public function capability() { return 'read'; }\n}\n");
    $this->put('pages/Implicit.php', "<?php\nclass Implicit extends Page {\n  function capability() { return 'read'; }\n}\n");
    $this->put('pages/Hidden.php', "<?php\nclass Hidden extends Page {\n  protected function capability() { return 'read'; }\n}\n");
    $this->put('pages/Needy.php', "<?php\nclass Needy extends Page {\n  public function capability(\$user) { return 'read'; }\n}\n");
    $this->put('pages/Optional.php', "<?php\nclass Optional extends Page {\n  public function capability(\$user = null) { return 'read'; }\n}\n");

    $run = $this->convert();

    $this->assertStringContainsString('pages/Commented.php declares no capability()', $run['output']);
    $this->assertStringNotContainsString('pages/Implicit.php', $run['output']);
    $this->assertStringContainsString('pages/Hidden.php declares no capability()', $run['output']);
    $this->assertStringContainsString('pages/Needy.php declares no capability()', $run['output']);
    $this->assertStringNotContainsString('pages/Optional.php', $run['output']);
  }

  public function test_a_plugin_that_declares_everything_has_nothing_to_review(): void
  {
    $this->put('config/routes.php', "<?php\nreturn ['p' => ['title' => 'P', 'capability' => 'read', 'route' => ['get' => 'A@b']]];\n");
    $this->put('config/menus.php', "<?php\nreturn [];\n");
    $this->put('api/v/v1/route.php', "<?php\nRoute::get('/x', fn() => 1, ['permission_callback' => '__return_true']);\n");

    $run = $this->convert();

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString('Nothing to change', $run['output']);
  }
}
