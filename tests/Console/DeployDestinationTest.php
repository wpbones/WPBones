<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\BonesProcess;

/**
 * Issue #139: with a destination whose parent folder did not exist, the copy failed on every
 * file, nothing said so, and the deploy printed "Deploy completed!" and exited 0. The missing
 * parents are now created as `mkdir -p` would, and a copy that still fails ends the deploy
 * with status 1.
 */
#[Group('console')]
final class DeployDestinationTest extends TestCase
{
  private BonesProcess $bones;

  protected function setUp(): void
  {
    parent::setUp();

    $this->bones = new BonesProcess();

    mkdir($this->bones->plugin . '/src');
    file_put_contents($this->bones->plugin . '/src/Kernel.php', "<?php // kernel\n");
    file_put_contents($this->bones->plugin . '/.hidden', "left out on purpose\n");
  }

  protected function tearDown(): void
  {
    // A read-only folder cannot be emptied.
    @chmod($this->bones->root . '/locked', 0755);
    $this->bones->remove();

    parent::tearDown();
  }

  public function test_missing_parent_folders_are_created(): void
  {
    $target = $this->bones->root . '/missing-parent/deeper/my-plugin';

    $run = $this->bones->run(['deploy', $target, '--no-build']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString('Deploy completed', $run['output']);
    $this->assertFileExists($target . '/wp-kirk.php');
    $this->assertFileExists($target . '/src/Kernel.php');
    $this->assertStringNotContainsString('Warning', $run['output']);
  }

  public function test_a_file_skipped_on_purpose_is_not_a_failure(): void
  {
    $target = $this->bones->root . '/out/my-plugin';

    $run = $this->bones->run(['deploy', $target, '--no-build']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertFileDoesNotExist($target . '/.hidden');
    $this->assertFileDoesNotExist($target . '/bones');
  }

  public function test_a_parent_that_is_a_regular_file_fails_the_deploy(): void
  {
    file_put_contents($this->bones->root . '/not-a-folder', 'x');

    $run = $this->bones->run(['deploy', $this->bones->root . '/not-a-folder/my-plugin', '--no-build']);

    $this->assertSame(1, $run['status'], $run['output']);
    $this->assertStringNotContainsString('Deploy completed', $run['output']);
    $this->assertStringContainsString('Deploy failed', $run['stderr']);
  }

  public function test_a_read_only_parent_fails_the_deploy(): void
  {
    if (PHP_OS_FAMILY === 'Windows' || (function_exists('posix_geteuid') && posix_geteuid() === 0)) {
      $this->markTestSkipped('A read-only folder is not enforced here.');
    }

    mkdir($this->bones->root . '/locked');
    chmod($this->bones->root . '/locked', 0555);

    $run = $this->bones->run(['deploy', $this->bones->root . '/locked/my-plugin', '--no-build']);

    $this->assertSame(1, $run['status'], $run['output']);
    $this->assertStringNotContainsString('Deploy completed', $run['output']);
    $this->assertStringContainsString('Deploy failed', $run['stderr']);
  }

  /** The recursive mkdir must come after the refusals, or it would let a refused path create folders. */
  public function test_a_refused_destination_creates_nothing(): void
  {
    $inside = $this->bones->run(['deploy', 'made-by-mistake/deeper', '--no-build']);

    $this->assertNotSame(0, $inside['status'], $inside['output']);
    $this->assertStringContainsString('inside the plugin', $inside['stderr']);
    $this->assertDirectoryDoesNotExist($this->bones->plugin . '/made-by-mistake');

    $parent = $this->bones->run(['deploy', 'made-by-mistake/../..', '--no-build']);

    $this->assertNotSame(0, $parent['status'], $parent['output']);
    $this->assertStringContainsString('contains the plugin', $parent['stderr']);
    $this->assertDirectoryDoesNotExist($this->bones->plugin . '/made-by-mistake');
    $this->assertFileExists($this->bones->plugin . '/wp-kirk.php');
  }

  /**
   * PHP's recursive mkdir collapses "link/.." lexically, the OS follows the link first; the guard
   * resolves like the OS, so the folder must land where the guard looked (Codex review of #143).
   */
  public function test_a_symlink_followed_by_dot_dot_creates_where_the_os_resolves(): void
  {
    mkdir($this->bones->root . '/a/b', 0777, true);
    symlink($this->bones->root . '/a/b', $this->bones->root . '/link');

    $run = $this->bones->run(['deploy', $this->bones->root . '/link/../x', '--no-build']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertFileExists($this->bones->root . '/a/x/wp-kirk.php');
    $this->assertDirectoryDoesNotExist($this->bones->root . '/x');
  }

  public function test_a_refused_destination_behind_a_symlink_creates_nothing(): void
  {
    mkdir($this->bones->plugin . '/sub');
    mkdir($this->bones->root . '/out');
    symlink($this->bones->plugin . '/sub', $this->bones->root . '/out/alias');

    $run = $this->bones->run(['deploy', '../out/alias/../deep/er', '--no-build']);

    $this->assertNotSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString('inside the plugin', $run['stderr']);
    $this->assertDirectoryDoesNotExist($this->bones->plugin . '/deep');
    $this->assertDirectoryDoesNotExist($this->bones->root . '/out/deep');
  }

  /** deleteDirectory() built a glob() pattern from the path, so "[ab]" matched the folders xa and xb. */
  public function test_a_destination_with_glob_characters_replaces_only_itself(): void
  {
    $old = $this->bones->root . '/x[ab]';
    mkdir($old);
    file_put_contents($old . '/wp-kirk.php', "<?php\n/**\n * Plugin Name: WP Kirk\n */\n");
    file_put_contents($old . '/stale.txt', 'old');
    mkdir($this->bones->root . '/xa');
    file_put_contents($this->bones->root . '/xa/SENTINEL.txt', 'keep me');

    $run = $this->bones->run(['deploy', $old, '--no-build']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertFileDoesNotExist($old . '/stale.txt');
    $this->assertFileExists($old . '/src/Kernel.php');
    $this->assertFileExists($this->bones->root . '/xa/SENTINEL.txt');
  }

  /** is_dir() follows a link, so the old delete emptied the folder a link in the destination pointed at. */
  public function test_replacing_a_destination_does_not_follow_a_symlinked_folder(): void
  {
    $old = $this->bones->root . '/out';
    mkdir($old);
    file_put_contents($old . '/wp-kirk.php', "<?php\n/**\n * Plugin Name: WP Kirk\n */\n");
    mkdir($this->bones->root . '/precious');
    file_put_contents($this->bones->root . '/precious/SENTINEL.txt', 'keep me');
    symlink($this->bones->root . '/precious', $old . '/linked');

    $run = $this->bones->run(['deploy', $old, '--no-build']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertFileExists($this->bones->root . '/precious/SENTINEL.txt');
    $this->assertFalse(is_link($old . '/linked'));
  }

  public function test_a_nested_copy_that_fails_fails_the_deploy(): void
  {
    if (PHP_OS_FAMILY === 'Windows' || (function_exists('posix_geteuid') && posix_geteuid() === 0)) {
      $this->markTestSkipped('An unreadable file is not enforced here.');
    }

    file_put_contents($this->bones->plugin . '/src/Unreadable.php', "<?php // secret\n");
    chmod($this->bones->plugin . '/src/Unreadable.php', 0000);

    $run = $this->bones->run(['deploy', $this->bones->root . '/out/my-plugin', '--no-build']);

    $this->assertSame(1, $run['status'], $run['output']);
    $this->assertStringContainsString('Unreadable.php', $run['stderr']);
    $this->assertStringNotContainsString('Deploy completed', $run['output']);
  }
}
