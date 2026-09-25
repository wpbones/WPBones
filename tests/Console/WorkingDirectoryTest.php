<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\BonesProcess;

/**
 * Up to 2.0.12 bones worked only from the plugin root (bones CLI audit, H7): `namespace`,
 * `readme.txt`, the stubs and every `make:*` target were read against the shell's directory,
 * so `php my-plugin/bones <anything>` died on `getNamespace(): Return value must be of type
 * string, null returned`. It now moves into the plugin first; a path typed on the command
 * line keeps meaning the shell's directory.
 */
#[Group('console')]
final class WorkingDirectoryTest extends TestCase
{
  private BonesProcess $bones;

  protected function setUp(): void
  {
    parent::setUp();

    $this->bones = (new BonesProcess())->withStubs();
  }

  protected function tearDown(): void
  {
    $this->bones->remove();

    parent::tearDown();
  }

  public function test_the_help_works_from_the_parent_folder(): void
  {
    $run = $this->bones->runFromParent(['--help']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString("'wp-kirk.php', 'WP Kirk', 'WPKirk'", $run['stdout']);
    $this->assertStringNotContainsString('TypeError', $run['output']);
  }

  public function test_make_writes_into_the_plugin_when_run_from_the_parent_folder(): void
  {
    $run = $this->bones->withFakeComposer(0)->runFromParent(['make:controller', 'Probe']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertFileExists($this->bones->plugin . '/plugin/Http/Controllers/Probe.php');
    $this->assertDirectoryDoesNotExist($this->bones->root . '/plugin');
  }

  public function test_version_reads_the_plugin_files_from_the_parent_folder(): void
  {
    $run = $this->bones->runFromParent(['version', '1.2.0'], "y\n");

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString('Stable tag: 1.2.0', (string) file_get_contents($this->bones->plugin . '/readme.txt'));
  }

  /**
   * Custom commands find WordPress and vendor/autoload.php through PWD (Command::loadWordPress()),
   * so bones leaves it as a `cd` into the plugin would: the folder as the shell spells it, here
   * through the /var symlink macOS puts in front of /private/var.
   */
  public function test_pwd_and_the_working_directory_name_the_plugin(): void
  {
    $run = $this->bones->runFromParent(['tinker'], "echo \$_SERVER['PWD'] . '|' . getenv('PWD') . '|' . getcwd();\nexit\n");

    $this->assertSame(0, $run['status'], $run['output']);
    $stdout = (string) preg_replace('/\e\[[0-9;]*[A-Za-z]/', '', $run['stdout']);
    $this->assertSame(1, preg_match('/^([^|\n]+)\|([^|\n]+)\|([^|\n]+)$/m', $stdout, $paths), $stdout);

    $this->assertSame($this->bones->plugin, $paths[1], 'PWD is the plugin as the shell spells it');
    $this->assertSame($this->bones->plugin, $paths[2], 'and so is the PWD child processes inherit');
    $this->assertSame(realpath($this->bones->plugin), realpath($paths[3]), 'the working directory is the plugin');
  }

  public function test_a_relative_deploy_path_means_the_folder_it_was_typed_in(): void
  {
    $run = $this->bones->runFromParent(['deploy', 'out', '--no-build']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertFileExists($this->bones->root . '/out/wp-kirk.php');
    $this->assertDirectoryDoesNotExist($this->bones->plugin . '/out');
  }

  public function test_a_relative_deploy_path_from_the_plugin_root_is_unchanged(): void
  {
    $run = $this->bones->run(['deploy', '../out', '--no-build']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertFileExists($this->bones->root . '/out/wp-kirk.php');
    $this->assertStringContainsString('../out', $run['stdout']);
  }
}
