<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Routing\AdminRouteProvider;

/**
 * Audit S2 (2026-09-18): the pages of config/routes.php are written straight into
 * $_registered_pages, with no menu entry, and WordPress lets any logged-in user open a
 * page registered that way. The `capability` key the boilerplate and the docs declare
 * was never read: a subscriber opened a route declared `manage_options`.
 */
final class AdminRouteProviderTest extends TestCase
{
  /** What add_action received, per hook, as [priority, callback]. */
  private array $added = [];

  /** The capabilities current_user_can() was asked about, in order. */
  private array $asked = [];

  /** The capabilities the current user has. */
  private array $granted = [];

  /** The messages _doing_it_wrong() received. */
  private array $notices = [];

  private string $basePath;

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $this->added = [];
    $this->asked = [];
    $this->granted = [];
    $this->notices = [];

    Functions\when('plugin_basename')->returnArg();
    Functions\when('get_plugin_page_hookname')->alias(fn($slug) => "toplevel_page_{$slug}");
    Functions\when('__')->returnArg();
    Functions\when('current_user_can')->alias(function ($capability) {
      $this->asked[] = $capability;

      return in_array($capability, $this->granted, true);
    });
    Functions\when('wp_die')->alias(function ($message = '', $status = 0) {
      throw new \RuntimeException((string) $message, (int) $status);
    });
    Functions\when('_doing_it_wrong')->alias(function ($function, $message) {
      $this->notices[] = $message;
    });

    foreach (['load-toplevel_page_my_page', 'toplevel_page_my_page'] as $hook) {
      Actions\expectAdded($hook)
        ->zeroOrMoreTimes()
        ->whenHappen(function ($callback, $priority = 10) use ($hook) {
          $this->added[$hook][] = [$priority, $callback];
        });
    }

    $this->basePath = sys_get_temp_dir() . '/wpbones-tests-' . bin2hex(random_bytes(6));
    mkdir($this->basePath . '/config', 0777, true);
  }

  protected function tearDown(): void
  {
    @unlink($this->basePath . '/config/routes.php');
    @rmdir($this->basePath . '/config');
    @rmdir($this->basePath);

    unset($GLOBALS['admin_page_hooks'], $GLOBALS['_registered_pages'], $GLOBALS['_parent_pages'], $GLOBALS['title']);

    Monkey\tearDown();
    parent::tearDown();
  }

  /** Register one route page, `my_page`, with the given extra keys. */
  private function register(array $page): void
  {
    $page += ['title' => 'My page', 'route' => ['get' => 'Dashboard\DashboardController@index']];

    file_put_contents($this->basePath . '/config/routes.php', '<?php return ' . var_export(['my_page' => $page], true) . ';');

    $plugin = new class ($this->basePath) {
      public function __construct(public string $basePath)
      {
      }

      public function csrfAction(): string
      {
        return 'my_plugin_csrf';
      }

      public function getCallableHook($routes)
      {
        return function () {
          return 'rendered';
        };
      }
    };

    (new AdminRouteProvider($plugin))->register();
  }

  /** Run every callback added to a hook, lowest priority first, as WordPress would. */
  private function fire(string $hook): array
  {
    $callbacks = $this->added[$hook] ?? [];
    usort($callbacks, fn($a, $b) => $a[0] <=> $b[0]);

    return array_map(fn($entry) => ($entry[1])(), $callbacks);
  }

  public function test_a_user_without_the_declared_capability_is_refused_on_load(): void
  {
    $this->register(['capability' => 'manage_options']);
    $this->granted = ['read'];

    // As wp-admin/includes/menu.php does before its own refusal: multisite hangs its
    // "you have no role here" splash on this action.
    Actions\expectDone('admin_page_access_denied')->once();

    try {
      $this->fire('load-toplevel_page_my_page');
      $this->fail('A subscriber opened a page declared manage_options.');
    } catch (\RuntimeException $e) {
      $this->assertSame(403, $e->getCode());
      $this->assertSame('Sorry, you are not allowed to access this page.', $e->getMessage());
    }

    $this->assertSame(['manage_options'], $this->asked);
  }

  public function test_a_user_with_the_declared_capability_gets_the_page(): void
  {
    $this->register(['capability' => 'manage_options']);
    $this->granted = ['read', 'manage_options'];

    $this->fire('load-toplevel_page_my_page');

    $this->assertContains('rendered', $this->fire('toplevel_page_my_page'));
    $this->assertSame(['manage_options', 'manage_options'], $this->asked);
  }

  public function test_the_page_itself_is_guarded_too(): void
  {
    $this->register(['capability' => 'manage_options']);
    $this->granted = ['read'];

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionCode(403);

    $this->fire('toplevel_page_my_page');
  }

  public function test_the_guard_runs_before_any_load_callback(): void
  {
    $this->register(['capability' => 'manage_options', 'route' => ['get' => 'A@b', 'load' => 'A@load']]);

    $priorities = array_column($this->added['load-toplevel_page_my_page'], 0);

    $this->assertSame(PHP_INT_MIN, min($priorities));
    // Two guards at PHP_INT_MIN, the capability's and, since 3.0, the nonce's (CsrfTest).
    $this->assertCount(2, array_keys($priorities, PHP_INT_MIN, true));
  }

  /** 3.0: a page that declares nothing is an admin page. In 2.x it asked for `read`. */
  public function test_a_page_without_a_capability_asks_for_manage_options(): void
  {
    $this->register([]);
    $this->granted = ['read'];

    try {
      $this->fire('load-toplevel_page_my_page');
      $this->fail('A subscriber opened a page that declares no capability.');
    } catch (\RuntimeException $e) {
      $this->assertSame(403, $e->getCode());
    }

    $this->assertSame(['manage_options'], $this->asked);
  }

  /** A page meant for every user with a role says so. */
  public function test_a_page_that_declares_read_is_open_to_a_subscriber(): void
  {
    $this->register(['capability' => 'read']);
    $this->granted = ['read'];

    $this->fire('load-toplevel_page_my_page');

    $this->assertSame(['read'], $this->asked);
  }

  /**
   * A page with no menu entry has no title for get_admin_page_title(), and admin-header.php
   * passed the null to strip_tags(): a deprecation in debug.log on every visit, PHP 8.1+.
   */
  public function test_the_page_gives_wordpress_its_title_on_load(): void
  {
    $this->register([]);
    $this->granted = ['read', 'manage_options'];

    $this->fire('load-toplevel_page_my_page');

    $this->assertSame('My page', $GLOBALS['title'] ?? null);
  }

  public function test_an_empty_capability_falls_back_to_manage_options(): void
  {
    $this->register(['capability' => '']);
    $this->granted = ['read', 'manage_options'];

    $this->fire('load-toplevel_page_my_page');

    $this->assertSame(['manage_options'], $this->asked);
    $this->assertSame([], $this->notices);
  }

  /**
   * A cast would ask WordPress for `Array` (no one has it) or for `1` (a user level that
   * contributors pass). A capability is a string: anything else is reported, and the page
   * asks for the default, `manage_options`.
   */
  #[DataProvider('capabilitiesThatAreNotStrings')]
  public function test_a_capability_that_is_not_a_string_falls_back_to_manage_options_with_a_notice($capability): void
  {
    $this->register(['capability' => $capability]);
    $this->granted = ['read', 'manage_options'];

    $this->fire('load-toplevel_page_my_page');

    $this->assertSame(['manage_options'], $this->asked);
    $this->assertCount(1, $this->notices);
    $this->assertStringContainsString('my_page', $this->notices[0]);
  }

  public static function capabilitiesThatAreNotStrings(): array
  {
    return [
      'an array' => [['manage_options']],
      'true' => [true],
      'an integer' => [1],
    ];
  }
}
