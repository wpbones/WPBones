<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\BonesProcess;

/**
 * `php bones tinker` up to 2.0.9 (bones CLI audit, 2026-09-24): it caught Exception but not
 * Error, so a typo printed nothing at all; its catch block ran eval() on the exception's
 * message instead of printing it; and every prompt was a recursive call from `finally`, so
 * input that ended without `exit` recursed until the process died with status 255.
 *
 * Tinker needs no WordPress to be exercised: it warns and carries on when wp-load.php is not
 * found, which is the case in the fixture.
 */
#[Group('console')]
final class TinkerTest extends TestCase
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

  public function test_an_error_is_shown(): void
  {
    $run = $this->bones->run(['tinker'], "undefined_fn_xyz();\nexit\n");

    $this->assertFalse($run['timedOut'], $run['output']);
    $this->assertStringContainsString('Call to undefined function undefined_fn_xyz()', $run['output']);
  }

  public function test_an_exception_message_is_printed_not_evaluated(): void
  {
    $run = $this->bones->run(['tinker'], "throw new \\Exception('print 6*7;');\nexit\n");

    $this->assertStringContainsString('print 6*7;', $run['output']);
    $this->assertStringNotContainsString('42', $run['output'], 'the exception message was run as code');
  }

  public function test_the_session_ends_with_its_input(): void
  {
    $run = $this->bones->run(['tinker'], "print 1+1;\n", 10);

    $this->assertFalse($run['timedOut'], 'tinker kept waiting after its input ended');
    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString('2', $run['stdout']);
  }

  public function test_a_returned_value_is_printed(): void
  {
    $run = $this->bones->run(['tinker'], "return 6*7;\nexit\n");

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString('42', $run['stdout']);
  }

  public function test_a_session_keeps_going_after_an_error(): void
  {
    $run = $this->bones->run(['tinker'], "undefined_fn_xyz();\nprint 'still here';\nexit\n");

    $this->assertStringContainsString('still here', $run['stdout']);
  }
}
