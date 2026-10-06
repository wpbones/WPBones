<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Routing\Pages\PageProvider;

/**
 * Audit S2 (2026-09-18), the `pages/` half: every class in `pages/` became an admin page
 * any logged-in user could open, and Page had no way to say otherwise. Page::capability()
 * now says which capability opens it; since 3.0 `manage_options` unless the page says otherwise.
 */
final class PageProviderTest extends TestCase
{
  /** What add_action received, per hook, as [priority, callback]. */
  private array $added = [];

  /** The capabilities current_user_can() was asked about, in order. */
  private array $asked = [];

  /** The capabilities the current user has. */
  private array $granted = [];

  private string $basePath;

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $this->added = [];
    $this->asked = [];
    $this->granted = [];

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

    $this->basePath = sys_get_temp_dir() . '/wpbones-tests-' . bin2hex(random_bytes(6));
    mkdir($this->basePath . '/pages', 0777, true);
  }

  protected function tearDown(): void
  {
    foreach (glob($this->basePath . '/pages/*.php') as $file) {
      unlink($file);
    }
    @rmdir($this->basePath . '/pages');
    @rmdir($this->basePath);

    unset($GLOBALS['admin_page_hooks'], $GLOBALS['_registered_pages'], $GLOBALS['_parent_pages'], $GLOBALS['title']);

    Monkey\tearDown();
    parent::tearDown();
  }

  /**
   * Write `pages/<slug>.php` with a Page subclass under a fresh class name (a class can
   * be declared once per process) and register the folder.
   */
  private function register(string $slug, string $body = '', string $extends = ' extends Page'): void
  {
    $class = 'TestPage' . bin2hex(random_bytes(6));

    file_put_contents(
      "{$this->basePath}/pages/{$slug}.php",
      "<?php\n\nuse WPKirk\\WPBones\\Routing\\Pages\\Support\\Page;\n\n" .
        "class {$class}{$extends}\n{\n" .
        "  public function title() { return 'A page'; }\n\n" .
        "  public function render() { return 'rendered'; }\n\n" .
        "{$body}}\n"
    );

    foreach (["load-toplevel_page_{$slug}", "toplevel_page_{$slug}"] as $hook) {
      Actions\expectAdded($hook)
        ->zeroOrMoreTimes()
        ->whenHappen(function ($callback, $priority = 10) use ($hook) {
          $this->added[$hook][] = [$priority, $callback];
        });
    }

    $plugin = new class ($this->basePath) {
      public function __construct(public string $basePath)
      {
      }

      public function csrfAction(): string
      {
        return 'my_plugin_csrf';
      }
    };

    (new PageProvider($plugin))->register();
  }

  /** Run every callback added to a hook, lowest priority first, as WordPress would. */
  private function fire(string $hook): void
  {
    $callbacks = $this->added[$hook] ?? [];
    usort($callbacks, fn($a, $b) => $a[0] <=> $b[0]);

    foreach ($callbacks as [, $callback]) {
      $callback();
    }
  }

  public function test_a_page_that_asks_for_a_capability_refuses_a_user_without_it(): void
  {
    $this->register('settings', "  public function capability() { return 'manage_options'; }\n");
    $this->granted = ['read'];

    try {
      $this->fire('load-toplevel_page_settings');
      $this->fail('A subscriber opened a page asking for manage_options.');
    } catch (\RuntimeException $e) {
      $this->assertSame(403, $e->getCode());
    }

    $this->assertSame(['manage_options'], $this->asked);
  }

  public function test_the_page_renders_for_a_user_with_the_capability(): void
  {
    $this->register('settings', "  public function capability() { return 'manage_options'; }\n");
    $this->granted = ['read', 'manage_options'];

    $this->fire('load-toplevel_page_settings');

    ob_start();
    $this->fire('toplevel_page_settings');
    $this->assertSame('rendered', ob_get_clean());
  }

  public function test_the_render_is_guarded_too(): void
  {
    $this->register('settings', "  public function capability() { return 'manage_options'; }\n");
    $this->granted = ['read'];

    ob_start();
    try {
      $this->fire('toplevel_page_settings');
      $this->fail('The page rendered for a subscriber.');
    } catch (\RuntimeException $e) {
      $this->assertSame(403, $e->getCode());
    } finally {
      $output = ob_get_clean();
    }

    $this->assertSame('', $output);
  }

  /** 3.0: a page that says nothing is an admin page. In 2.x it asked for `read`. */
  public function test_a_page_that_says_nothing_asks_for_manage_options(): void
  {
    $this->register('about');
    $this->granted = ['read'];

    try {
      $this->fire('load-toplevel_page_about');
      $this->fail('A subscriber opened a page that declares no capability.');
    } catch (\RuntimeException $e) {
      $this->assertSame(403, $e->getCode());
    }

    $this->assertSame(['manage_options'], $this->asked);
  }

  public function test_a_page_that_asks_for_read_is_open_to_a_subscriber(): void
  {
    $this->register('about', "  public function capability() { return 'read'; }\n");
    $this->granted = ['read'];

    $this->fire('load-toplevel_page_about');

    $this->assertSame(['read'], $this->asked);
  }

  /**
   * PageProvider never required `extends Page`: it calls title() and render(). A class that
   * only has those two must still load, and ask for the default, not stop wp-admin with a fatal.
   */
  public function test_a_class_that_does_not_extend_page_asks_for_manage_options(): void
  {
    $this->register('legacy', '', '');
    $this->granted = ['read', 'manage_options'];

    $this->fire('load-toplevel_page_legacy');

    $this->assertSame(['manage_options'], $this->asked);
  }

  /**
   * is_callable() is true through __call() as well (Codex, round 2): a page that forwards
   * unknown methods must not be asked for a capability it never declared.
   */
  public function test_a_page_with_call_but_no_capability_method_asks_for_manage_options(): void
  {
    $this->register('magic', "  public function __call(\$name, \$args) { throw new \\LogicException(\$name); }\n");
    $this->granted = ['read', 'manage_options'];

    $this->fire('load-toplevel_page_magic');

    $this->assertSame(['manage_options'], $this->asked);
  }

  /** A capability() that needs arguments is not the method this check can call. */
  public function test_a_capability_method_with_required_arguments_is_not_called(): void
  {
    $this->register('args', "  public function capability(\$user) { return 'read'; }\n");
    $this->granted = ['read', 'manage_options'];

    $this->fire('load-toplevel_page_args');

    $this->assertSame(['manage_options'], $this->asked);
  }

  /** The same null title as a route page: see AdminRouteProviderTest. */
  public function test_the_page_gives_wordpress_its_title_on_load(): void
  {
    $this->register('about');
    $this->granted = ['read', 'manage_options'];

    $this->fire('load-toplevel_page_about');

    $this->assertSame('A page', $GLOBALS['title'] ?? null);
  }

  public function test_the_guard_runs_before_any_other_load_callback(): void
  {
    $this->register('about');

    $priorities = array_column($this->added['load-toplevel_page_about'], 0);

    $this->assertSame(PHP_INT_MIN, min($priorities));
  }
}
