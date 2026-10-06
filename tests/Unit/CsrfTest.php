<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Routing\AdminMenuProvider;
use WPKirk\WPBones\Routing\AdminRouteProvider;
use WPKirk\WPBones\Routing\Csrf;

/**
 * Audit S4 (2026-09-18): a WP Bones admin page maps the HTTP verb to store()/update()/destroy()
 * and runs its `load` callbacks with no nonce, so a form on another site could post into it
 * with the victim's cookies. Since 3.0 every request to a WP Bones admin page that is not a GET
 * or a HEAD carries the plugin's nonce ($plugin->csrfField() in the form), checked before any
 * load callback and again before the page renders; a route says 'csrf' => false to opt out.
 */
final class CsrfTest extends TestCase
{
  /** What add_action received, per hook, as [priority, callback]. */
  private array $added = [];

  /** The nonces wp_verify_nonce() accepts, as "value|action". */
  private array $valid = [];

  private array $server;

  private array $post;

  private array $requestVars;

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $this->added = [];
    $this->valid = [];
    $this->server = $_SERVER;
    $this->post = $_POST;
    $this->requestVars = $_REQUEST;

    Functions\when('wp_verify_nonce')->alias(fn($nonce, $action) => in_array("{$nonce}|{$action}", $this->valid, true) ? 1 : false);
    Functions\when('wp_unslash')->returnArg();
    Functions\when('sanitize_text_field')->returnArg();
    Functions\when('wp_nonce_ays')->alias(function ($action) {
      throw new \RuntimeException("refused: {$action}", 403);
    });

    Actions\expectAdded('load-my_hook')->zeroOrMoreTimes()->whenHappen(function ($callback, $priority = 10) {
      $this->added['load-my_hook'][] = [$priority, $callback];
    });
    Actions\expectAdded('my_hook')->zeroOrMoreTimes()->whenHappen(function ($callback, $priority = 10) {
      $this->added['my_hook'][] = [$priority, $callback];
    });
  }

  protected function tearDown(): void
  {
    $_SERVER = $this->server;
    $_POST = $this->post;
    $_REQUEST = $this->requestVars;
    Monkey\tearDown();
    parent::tearDown();
  }

  private function fire(string $hook): void
  {
    foreach ($this->added[$hook] ?? [] as [, $callback]) {
      $callback();
    }
  }

  private function request(string $method, array $post = []): void
  {
    $_SERVER['REQUEST_METHOD'] = $method;
    // As PHP fills them for a form post: $_REQUEST holds $_POST too.
    $_POST = $post;
    $_REQUEST = $post;
  }

  public function test_a_post_without_the_nonce_is_refused_before_any_load_callback(): void
  {
    Csrf::guard('my_hook', 'my_plugin_csrf');
    $this->request('POST');

    $this->assertSame(PHP_INT_MIN, $this->added['load-my_hook'][0][0]);

    try {
      $this->fire('load-my_hook');
      $this->fail('A POST without a nonce went through.');
    } catch (\RuntimeException $e) {
      $this->assertSame(403, $e->getCode());
      $this->assertSame('refused: my_plugin_csrf', $e->getMessage());
    }
  }

  public function test_the_render_is_guarded_too(): void
  {
    Csrf::guard('my_hook', 'my_plugin_csrf');
    $this->request('POST', ['_wpbones_nonce' => 'forged']);

    $this->expectException(\RuntimeException::class);

    $this->fire('my_hook');
  }

  public function test_a_post_with_the_nonce_goes_through(): void
  {
    Csrf::guard('my_hook', 'my_plugin_csrf');
    $this->valid = ['abc|my_plugin_csrf'];
    $this->request('POST', ['_wpbones_nonce' => 'abc']);

    $this->fire('load-my_hook');
    $this->fire('my_hook');

    $this->addToAssertionCount(1);
  }

  /** The nonce of another action, a form's own wp_nonce_field() for instance, is not this one. */
  public function test_a_nonce_for_another_action_is_refused(): void
  {
    Csrf::guard('my_hook', 'my_plugin_csrf');
    $this->valid = ['abc|Options'];
    $this->request('POST', ['_wpbones_nonce' => 'abc', '_wpnonce' => 'abc']);

    $this->expectException(\RuntimeException::class);

    $this->fire('load-my_hook');
  }

  public function test_get_and_head_need_no_nonce(): void
  {
    Csrf::guard('my_hook', 'my_plugin_csrf');

    foreach (['GET', 'HEAD'] as $method) {
      $this->request($method);
      $this->fire('load-my_hook');
      $this->fire('my_hook');
    }

    $this->addToAssertionCount(1);
  }

  /** PUT, PATCH and DELETE reach the page as a POST with _method: the POST is what is checked. */
  public function test_every_verb_but_get_and_head_needs_it(): void
  {
    Csrf::guard('my_hook', 'my_plugin_csrf');
    $refused = 0;

    foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
      $this->request($method);

      try {
        $this->fire('load-my_hook');
      } catch (\RuntimeException $e) {
        $refused++;
      }
    }

    $this->assertSame(4, $refused);
  }

  /** A fetch() with a JSON body has no $_POST: the nonce may come in the X-WPBones-Nonce header. */
  public function test_the_nonce_may_come_in_a_header(): void
  {
    Csrf::guard('my_hook', 'my_plugin_csrf');
    $this->valid = ['abc|my_plugin_csrf'];
    $this->request('PUT');
    $_SERVER['HTTP_X_WPBONES_NONCE'] = 'abc';

    try {
      $this->fire('load-my_hook');
    } finally {
      unset($_SERVER['HTTP_X_WPBONES_NONCE']);
    }

    $this->addToAssertionCount(1);
  }

  /**
   * WordPress's own forms that post to the page it is on, each with its own nonce: Screen Options
   * (when the plugin does not save the option itself) and the filesystem credentials form.
   */
  public function test_wordpress_s_own_nonced_posts_to_the_page_go_through(): void
  {
    Csrf::guard('my_hook', 'my_plugin_csrf');
    $this->valid = ['so|screen-options-nonce', 'fs|filesystem-credentials'];

    $this->request('POST', ['wp_screen_options' => ['option' => 'x'], 'screenoptionnonce' => 'so']);
    $this->fire('load-my_hook');

    $this->request('POST', ['_fs_nonce' => 'fs', 'hostname' => 'example.test']);
    $this->fire('load-my_hook');

    $this->request('POST', ['screenoptionnonce' => 'forged', 'store' => '1']);
    $this->expectException(\RuntimeException::class);
    $this->fire('load-my_hook');
  }

  public function test_the_field_carries_the_plugins_action(): void
  {
    Functions\when('wp_nonce_field')->alias(fn($action, $name, $referer, $echo) => "<input name=\"{$name}\" value=\"nonce-for-{$action}\">");

    $this->assertSame('<input name="_wpbones_nonce" value="nonce-for-my_plugin_csrf">', Csrf::field('my_plugin_csrf'));
  }

  /** The providers: a route page and a menu item are guarded unless their route says csrf false. */
  public function test_route_pages_and_menu_items_are_guarded_unless_they_opt_out(): void
  {
    $base = sys_get_temp_dir() . '/wpbones-tests-' . bin2hex(random_bytes(6));
    mkdir($base . '/config', 0777, true);

    file_put_contents($base . '/config/routes.php', '<?php return ' . var_export([
      'guarded' => ['title' => 'G', 'capability' => 'read', 'route' => ['post' => 'A@b']],
      'open' => ['title' => 'O', 'capability' => 'read', 'route' => ['post' => 'A@c', 'csrf' => false]],
    ], true) . ';');
    file_put_contents($base . '/config/menus.php', '<?php return ' . var_export([
      'my_menu' => ['menu_title' => 'M', 'capability' => 'read', 'items' => [
        ['menu_title' => 'First', 'route' => ['get' => 'A@d']],
        'webhook' => ['menu_title' => 'Hook', 'route' => ['post' => 'A@e', 'csrf' => false]],
      ]],
    ], true) . ';');

    $plugin = new class ($base) {
      public string $images = '';

      public function __construct(public string $basePath)
      {
      }

      public function getCallableHook($routes)
      {
        return fn() => 'rendered';
      }

      public function csrfAction(): string
      {
        return 'my_plugin_csrf';
      }
    };

    $guarded = [];
    Functions\when('plugin_basename')->returnArg();
    Functions\when('get_plugin_page_hookname')->alias(fn($slug) => "toplevel_page_{$slug}");
    Functions\when('sanitize_title')->alias(fn($title) => strtolower((string) $title));
    Functions\when('add_menu_page')->justReturn('toplevel_page_my_menu');
    Functions\when('add_submenu_page')->alias(fn($parent, $pageTitle, $menuTitle, $capability, $slug) => "my_menu_page_{$slug}");
    Functions\when('current_user_can')->justReturn(true);
    foreach (['toplevel_page_guarded', 'toplevel_page_open', 'my_menu_page_my_menu', 'my_menu_page_wpkirk_webhook'] as $hook) {
      Actions\expectAdded("load-{$hook}")->zeroOrMoreTimes()->whenHappen(function ($callback, $priority = 10) use (&$guarded, $hook) {
        if ($priority === PHP_INT_MIN) {
          $guarded[] = $hook;
        }
      });
    }

    (new AdminRouteProvider($plugin))->register();
    (new AdminMenuProvider($plugin))->register();

    foreach (glob($base . '/config/*') as $file) {
      unlink($file);
    }
    rmdir($base . '/config');
    rmdir($base);

    // The route pages: one guard for the capability, one for the nonce; none for the opted-out one.
    $this->assertSame(2, count(array_keys($guarded, 'toplevel_page_guarded', true)));
    $this->assertSame(1, count(array_keys($guarded, 'toplevel_page_open', true)));
    $this->assertContains('my_menu_page_my_menu', $guarded);
    $this->assertNotContains('my_menu_page_wpkirk_webhook', $guarded);
  }
}
