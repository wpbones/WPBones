<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Routing\API\Route;

/**
 * Audit S3 (2026-09-18): Route merged `'permission_callback' => '__return_true'` into every
 * route that did not set one, so a `Route::post()` with no options was a public write
 * endpoint and WordPress's own notice about the missing argument never fired.
 *
 * In 2.x the route stays public, as it was, and says so with a notice; 3.0 will refuse it.
 */
final class RestRouteTest extends TestCase
{
  /** What register_rest_route() received, as [namespace, route, args]. */
  private array $registered = [];

  /** The messages _doing_it_wrong() received. */
  private array $notices = [];

  private $restApiInit;

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $this->registered = [];
    $this->notices = [];
    $this->restApiInit = null;

    $this->resetRoutes();

    Functions\when('register_rest_route')->alias(function ($namespace, $route, $args) {
      $this->registered[] = [$namespace, $route, $args];

      return true;
    });
    Functions\when('_doing_it_wrong')->alias(function ($function, $message, $version) {
      $this->notices[] = [$function, $message, $version];
    });

    Actions\expectAdded('rest_api_init')->whenHappen(function ($callback) {
      $this->restApiInit = $callback;
    });

    Route::vendor('wpkirk/v1');
  }

  protected function tearDown(): void
  {
    $this->resetRoutes();
    Monkey\tearDown();
    parent::tearDown();
  }

  /** Route keeps its routes in a static property: start every test from none. */
  private function resetRoutes(): void
  {
    $apis = new \ReflectionProperty(Route::class, 'apis');
    $apis->setAccessible(true);
    $apis->setValue(null, []);
  }

  /** Register the routes defined so far, as RestProvider does, and run rest_api_init. */
  private function registerRoutes(): array
  {
    Route::register();
    ($this->restApiInit)();

    return array_column($this->registered, 2, 1);
  }

  public function test_a_route_without_a_permission_callback_stays_public_with_a_notice(): void
  {
    Route::post('/settings', fn() => 'saved');

    $args = $this->registerRoutes();

    $this->assertSame('__return_true', $args['/settings']['permission_callback']);
    $this->assertCount(1, $this->notices);
    [$function, $message, $version] = $this->notices[0];
    $this->assertSame('Route::post', $function);
    $this->assertStringContainsString('/wpkirk/v1/settings', $message);
    $this->assertStringContainsString("'permission_callback' => '__return_true'", $message);
    // No version: _doing_it_wrong() would print it as the WordPress version that added the message.
    $this->assertSame('', $version);
  }

  /** The notice belongs to rest_api_init, where WordPress registers routes, not to the route file. */
  public function test_the_notice_waits_for_rest_api_init(): void
  {
    Route::post('/settings', fn() => 'saved');

    $this->assertSame([], $this->notices);

    $this->registerRoutes();

    $this->assertCount(1, $this->notices);
  }

  /** WordPress's own check is isset(): a null callback is a missing one, there and here. */
  public function test_a_null_permission_callback_counts_as_missing(): void
  {
    Route::post('/settings', fn() => 'saved', ['permission_callback' => null]);

    $args = $this->registerRoutes();

    $this->assertSame('__return_true', $args['/settings']['permission_callback']);
    $this->assertCount(1, $this->notices);
  }

  public function test_request_without_a_permission_callback_gives_one_notice_per_verb(): void
  {
    Route::request(['get', 'POST'], '/multiple', fn() => 'ok');

    $this->registerRoutes();

    $this->assertSame(['Route::get', 'Route::post'], array_column($this->notices, 0));
  }

  public function test_a_declared_permission_callback_is_kept_and_raises_nothing(): void
  {
    $check = fn() => current_user_can('manage_options');
    Route::post('/settings', fn() => 'saved', ['permission_callback' => $check]);
    Route::get('/public', fn() => 'hello', ['permission_callback' => '__return_true']);

    $args = $this->registerRoutes();

    $this->assertSame($check, $args['/settings']['permission_callback']);
    $this->assertSame('__return_true', $args['/public']['permission_callback']);
    $this->assertSame([], $this->notices);
  }

  public function test_other_options_reach_register_rest_route_untouched(): void
  {
    Route::get('/example', fn() => 'hello', ['args' => ['id' => ['required' => true]]]);

    $args = $this->registerRoutes();

    $this->assertSame(['id' => ['required' => true]], $args['/example']['args']);
    $this->assertSame('GET', $args['/example']['methods']);
  }
}
