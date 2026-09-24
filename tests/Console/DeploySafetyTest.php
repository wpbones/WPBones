<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\BonesProcess;

/**
 * Up to 2.0.9 `bones deploy <path>` deleted whatever <path> was, after a five-second pause
 * and nothing else. `deploy ..` removed every plugin beside this one and the plugin itself;
 * `deploy build` copied the plugin into its own subfolder until the path was too long.
 * Both were reproduced before this was written (bones CLI audit, 2026-09-24).
 *
 * The destination is now refused when it is the plugin, one of its parents or inside it,
 * whatever the flags, and an existing folder is replaced only when it is empty or holds a
 * previous deploy of this plugin; anything else needs --force.
 */
#[Group('console')]
final class DeploySafetyTest extends TestCase
{
  private BonesProcess $bones;

  protected function setUp(): void
  {
    parent::setUp();

    $this->bones = new BonesProcess();

    file_put_contents($this->bones->root . '/SENTINEL.txt', 'keep me');
    mkdir($this->bones->root . '/neighbour-plugin');
    file_put_contents($this->bones->root . '/neighbour-plugin/main.php', "<?php // another plugin\n");
  }

  protected function tearDown(): void
  {
    $this->bones->remove();

    parent::tearDown();
  }

  private function assertNothingWasDeleted(string $output): void
  {
    $this->assertFileExists($this->bones->root . '/SENTINEL.txt', "a file beside the plugin was deleted:\n" . $output);
    $this->assertFileExists($this->bones->root . '/neighbour-plugin/main.php', "a neighbouring plugin was deleted:\n" . $output);
    $this->assertFileExists($this->bones->plugin . '/wp-kirk.php', "the plugin deleted itself:\n" . $output);
  }

  public function test_the_parent_folder_is_refused(): void
  {
    $run = $this->bones->run(['deploy', '..', '--no-build']);

    $this->assertNotSame(0, $run['status'], $run['output']);
    $this->assertNothingWasDeleted($run['output']);
    $this->assertStringContainsString('contains the plugin', $run['stderr']);
  }

  public function test_force_does_not_unlock_the_parent_folder(): void
  {
    $run = $this->bones->run(['deploy', '..', '--no-build', '--force']);

    $this->assertNotSame(0, $run['status'], $run['output']);
    $this->assertNothingWasDeleted($run['output']);
  }

  public function test_the_plugin_folder_itself_is_refused(): void
  {
    $run = $this->bones->run(['deploy', '.', '--no-build']);

    $this->assertNotSame(0, $run['status'], $run['output']);
    $this->assertNothingWasDeleted($run['output']);
    $this->assertStringContainsString('is the plugin itself', $run['stderr']);
  }

  /**
   * A symlink followed by "..": the filesystem resolves the link first, so the ".." climbs from
   * wherever the link points. Collapsing ".." first (the first version of the guard) checked
   * out/ while deleteDirectory() emptied the plugin (Codex review of #111).
   */
  public function test_a_symlink_followed_by_dot_dot_cannot_reach_the_plugin(): void
  {
    mkdir($this->bones->plugin . '/sub');
    mkdir($this->bones->root . '/out');
    symlink($this->bones->plugin . '/sub', $this->bones->root . '/out/alias');

    $run = $this->bones->run(['deploy', '../out/alias/..', '--no-build', '--force']);

    $this->assertNotSame(0, $run['status'], $run['output']);
    $this->assertNothingWasDeleted($run['output']);
    $this->assertStringContainsString('is the plugin itself', $run['stderr']);
  }

  public function test_a_folder_inside_the_plugin_is_refused(): void
  {
    $run = $this->bones->run(['deploy', 'build', '--no-build']);

    $this->assertNotSame(0, $run['status'], $run['output']);
    $this->assertDirectoryDoesNotExist($this->bones->plugin . '/build', 'the plugin was copied into itself');
    $this->assertStringContainsString('inside the plugin', $run['stderr']);
  }

  public function test_a_zip_whose_work_folder_would_sit_inside_the_plugin_is_refused(): void
  {
    $run = $this->bones->run(['deploy', 'package', '--create-zip', '--no-build']);

    $this->assertNotSame(0, $run['status'], $run['output']);
    $this->assertDirectoryDoesNotExist($this->bones->plugin . '/package-tmp');
  }

  public function test_an_unrelated_folder_is_kept(): void
  {
    mkdir($this->bones->root . '/out');
    file_put_contents($this->bones->root . '/out/important.txt', 'not a deploy');

    $run = $this->bones->run(['deploy', '../out', '--no-build']);

    $this->assertNotSame(0, $run['status'], $run['output']);
    $this->assertFileExists($this->bones->root . '/out/important.txt', $run['output']);
    $this->assertStringContainsString('--force', $run['stderr']);
  }

  public function test_force_replaces_an_unrelated_folder(): void
  {
    mkdir($this->bones->root . '/out');
    file_put_contents($this->bones->root . '/out/important.txt', 'not a deploy');

    $run = $this->bones->run(['deploy', '../out', '--no-build', '--force']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertFileDoesNotExist($this->bones->root . '/out/important.txt');
    $this->assertFileExists($this->bones->root . '/out/wp-kirk.php');
  }

  public function test_an_empty_folder_is_used(): void
  {
    mkdir($this->bones->root . '/out');

    $run = $this->bones->run(['deploy', '../out', '--no-build']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertFileExists($this->bones->root . '/out/wp-kirk.php');
  }

  public function test_a_previous_deploy_of_this_plugin_is_replaced_without_a_pause(): void
  {
    $first = $this->bones->run(['deploy', '../out', '--no-build']);
    $this->assertSame(0, $first['status'], $first['output']);

    file_put_contents($this->bones->root . '/out/stale.txt', 'left by the previous deploy');

    $started = microtime(true);
    $second = $this->bones->run(['deploy', '../out', '--no-build']);
    $elapsed = microtime(true) - $started;

    $this->assertSame(0, $second['status'], $second['output']);
    $this->assertFileDoesNotExist($this->bones->root . '/out/stale.txt');
    $this->assertFileExists($this->bones->root . '/out/wp-kirk.php');
    $this->assertLessThan(4.5, $elapsed, 'the deploy still waits before deleting the previous one');
    $this->assertNothingWasDeleted($second['output']);
  }

  public function test_a_deploy_of_another_plugin_is_not_mistaken_for_ours(): void
  {
    mkdir($this->bones->root . '/out');
    file_put_contents($this->bones->root . '/out/wp-kirk.php', "<?php\n/**\n * Plugin Name: Somebody Else\n */\n");

    $run = $this->bones->run(['deploy', '../out', '--no-build']);

    $this->assertNotSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString('Somebody Else', (string) file_get_contents($this->bones->root . '/out/wp-kirk.php'));
  }
}
