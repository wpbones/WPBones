<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\BonesProcess;

/**
 * Up to 2.0.9 almost every failure exited 0 (bones CLI audit, 2026-09-24): an unknown
 * command, a missing class name, and a confirmation answered "n" — or not answered at all,
 * which is what a script gets — all looked like success. `echo y |` in front of
 * `php bones version` was the only way a release script could tell.
 *
 * Errors now exit 1 and are written to STDERR; a declined confirmation says so and exits 1.
 */
#[Group('console')]
final class ExitCodeTest extends TestCase
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

  public function test_an_unknown_command_fails_on_stderr(): void
  {
    $run = $this->bones->run(['no-such-command']);

    $this->assertSame(1, $run['status'], $run['output']);
    $this->assertStringContainsString("Unknown command 'no-such-command'", $run['stderr']);
    $this->assertStringNotContainsString('Unknown command', $run['stdout']);
  }

  public function test_the_help_succeeds(): void
  {
    $run = $this->bones->run(['--help']);

    $this->assertSame(0, $run['status'], $run['output']);
  }

  public function test_a_missing_class_name_fails(): void
  {
    $run = $this->bones->run(['make:controller']);

    $this->assertSame(1, $run['status'], $run['output']);
    $this->assertStringContainsString('ClassName is required', $run['stderr']);
  }

  public function test_a_declined_version_fails_and_changes_nothing(): void
  {
    $run = $this->bones->run(['version', '2.0.0'], "n\n");

    $this->assertSame(1, $run['status'], $run['output']);
    $this->assertStringContainsString('Aborted', $run['output']);
    $this->assertStringContainsString('Stable tag: 1.0.0', (string) file_get_contents($this->bones->plugin . '/readme.txt'));
  }

  public function test_a_version_nobody_answered_fails(): void
  {
    $run = $this->bones->run(['version', '2.0.0'], '');

    $this->assertSame(1, $run['status'], $run['output']);
    $this->assertStringContainsString('Stable tag: 1.0.0', (string) file_get_contents($this->bones->plugin . '/readme.txt'));
  }

  public function test_a_declined_rename_fails_and_changes_nothing(): void
  {
    $run = $this->bones->run(['rename', 'My Plugin'], "n\n");

    $this->assertSame(1, $run['status'], $run['output']);
    $this->assertSame('WP Kirk,WPKirk', file_get_contents($this->bones->plugin . '/namespace'));
  }

  public function test_a_declined_migration_fails(): void
  {
    $run = $this->bones->run(['migrate:to-v2'], "n\n");

    $this->assertSame(1, $run['status'], $run['output']);
    $this->assertStringContainsString('aborted', $run['output']);
  }
}
