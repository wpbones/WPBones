<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Foundation\WordPressAjaxServiceProvider;

/**
 * Audit S5 (2026-09-18): a `logged` Ajax action of a provider without $nonceHash ran for any
 * request, its nonce check returning true, so a page on another site could call it with the
 * user's cookies; and useHTTPPost() handed back $_POST with WordPress's slashes. Since 3.0 a logged
 * action without a nonce to check refuses every request and says why; useHTTPPost() unslashes.
 * `trusted` and `notLogged` actions are public by design and unchanged.
 */
final class AjaxProviderTest extends TestCase
{
  /** The callbacks added per hook. */
  private array $added = [];

  /** What ran: the action methods and the JSON errors. */
  public array $ran = [];

  private array $notices = [];

  private array $post;

  protected function setUp(): void
  {
    parent::setUp();
    Monkey\setUp();

    $this->added = [];
    $this->ran = [];
    $this->notices = [];
    $this->post = $_POST;

    Functions\when('__')->returnArg();
    Functions\when('wp_unslash')->alias(fn($value) => is_string($value) ? stripslashes($value) : $value);
    Functions\when('current_user_can')->justReturn(true);
    Functions\when('wp_verify_nonce')->alias(fn($nonce, $action) => $nonce === "valid-{$action}" ? 1 : false);
    Functions\when('wp_send_json_error')->alias(function ($message, $status) {
      $this->ran[] = "error {$status}";
    });
    Functions\when('_doing_it_wrong')->alias(function ($function, $message) {
      $this->notices[] = $message;
    });

    Actions\expectAdded('wp_ajax_save')->zeroOrMoreTimes()->whenHappen(function ($callback) {
      $this->added['wp_ajax_save'][] = $callback;
    });
  }

  protected function tearDown(): void
  {
    $_POST = $this->post;
    Monkey\tearDown();
    parent::tearDown();
  }

  private function provider(string $nonceHash): WordPressAjaxServiceProvider
  {
    $test = $this;

    $provider = new class (null, $test, $nonceHash) extends WordPressAjaxServiceProvider {
      protected $logged = ['save'];

      public function __construct($plugin, private $test, string $hash)
      {
        parent::__construct($plugin);
        $this->nonceHash = $hash;
      }

      public function save()
      {
        $this->test->ran[] = 'save';
      }

      public function posted(...$args)
      {
        return $this->useHTTPPost(...$args);
      }
    };

    $provider->register();

    return $provider;
  }

  private function call(): void
  {
    foreach ($this->added['wp_ajax_save'] ?? [] as $callback) {
      $callback();
    }
  }

  public function test_a_logged_action_without_a_nonce_hash_refuses_every_request(): void
  {
    $this->provider('');
    $_POST = ['nonce' => 'anything'];

    $this->call();

    $this->assertSame(['error 403'], $this->ran);
    $this->assertNotEmpty($this->notices);
    $this->assertStringContainsString('$nonceHash', $this->notices[0]);
  }

  public function test_a_logged_action_with_its_nonce_runs(): void
  {
    $this->provider('my-plugin');
    $_POST = ['nonce' => 'valid-my-plugin'];

    $this->call();

    $this->assertSame(['save'], $this->ran);
  }

  public function test_a_logged_action_without_the_nonce_in_the_request_is_refused(): void
  {
    $this->provider('my-plugin');
    $_POST = [];

    $this->call();

    $this->assertSame(['error 403'], $this->ran);
  }

  public function test_use_http_post_unslashes(): void
  {
    $provider = $this->provider('my-plugin');
    $_POST = ['title' => "O\\'Reilly", 'missing' => null];

    $this->assertSame(["O'Reilly", null], $provider->posted('title', 'nothing'));
  }
}
