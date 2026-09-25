<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\BonesProcess;

/**
 * Up to 2.0.12 `rename` read and rewrote every file of the plugin, and `rename --update`, which
 * Composer runs after every install and update, went through the whole rename again (bones CLI
 * audit, M2). Measured on the Internationalization boilerplate: 187 files written, 99 of them
 * byte-identical, both compiled .mo catalogues corrupted; and in a renamed plugin with an
 * index.php in its root, `--update` moved index.php over the main plugin file.
 */
#[Group('console')]
final class RenameTest extends TestCase
{
  /** A compiled catalogue: binary, and it carries the plugin id. */
  private const MO = "\xde\x12\x04\x95\x00\x00\x00\x00\x01\x00\x00\x00X-Domain: wp-kirk\x00wp-kirk\x00";

  private BonesProcess $bones;

  protected function setUp(): void
  {
    parent::setUp();

    $this->bones = (new BonesProcess())->withFakeComposer(0);
  }

  protected function tearDown(): void
  {
    $this->bones->remove();

    parent::tearDown();
  }

  public function test_a_rename_leaves_binary_files_alone(): void
  {
    $this->write('wp-kirk.php', "<?php\n/**\n * Plugin Name: WP Kirk\n * Domain Path: /languages\n */\n");
    $this->write('languages/wp-kirk-it_IT.mo', self::MO);
    $this->write('plugin/Http/Foo.php', "<?php\nnamespace WPKirk\\Http;\n");

    $run = $this->bones->run(['rename', 'Probe Plugin'], "y\n");

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertSame("<?php\nnamespace ProbePlugin\\Http;\n", $this->read('plugin/Http/Foo.php'));
    $this->assertFileDoesNotExist($this->bones->plugin . '/languages/wp-kirk-it_IT.mo');
    $this->assertSame(self::MO, $this->read('languages/probe-plugin-it_IT.mo'), 'the catalogue is renamed, its bytes are not');
    $this->assertSame('Probe Plugin,ProbePlugin', $this->read('namespace'));
  }

  public function test_a_rename_writes_only_the_files_it_changes(): void
  {
    $this->write('plugin/Http/Foo.php', "<?php\nnamespace WPKirk\\Http;\n");
    $this->write('plugin/Http/Plain.php', "<?php\n// nothing to rename here\n");
    touch($this->bones->plugin . '/plugin/Http/Plain.php', 1000000000);
    clearstatcache();

    $run = $this->bones->run(['rename', 'Probe Plugin'], "y\n");

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString('ProbePlugin', $this->read('plugin/Http/Foo.php'));
    // Still 2001: a rewrite would stamp it with today. (Not assertSame: one run in a dozen read it
    // back a second later, with nothing written.)
    $this->assertLessThan(1000086400, filemtime($this->bones->plugin . '/plugin/Http/Plain.php'));
  }

  public function test_update_keeps_the_main_file_when_the_plugin_has_an_index_php(): void
  {
    $main = "<?php\n/**\n * Plugin Name: Probe Plugin\n */\nrequire __DIR__ . '/bootstrap/autoload.php';\n";
    $this->renamed($main);
    $this->write('index.php', "<?php\n// Silence is golden.\n");

    $run = $this->bones->run(['rename', '--update']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertSame($main, $this->read('probe-plugin.php'));
    $this->assertSame("<?php\n// Silence is golden.\n", $this->read('index.php'));
  }

  public function test_update_renames_vendor_and_nothing_else(): void
  {
    $this->renamed("<?php\n/**\n * Plugin Name: Probe Plugin\n */\n");
    $this->write('vendor/wpbones/wpbones/src/Foundation/Plugin.php', "<?php\nnamespace WPKirk\\WPBones\\Foundation;\n\$file = 'wp-kirk.php';\n");
    $this->write('vendor/composer/autoload_psr4.php', "<?php\nreturn ['WPKirk\\\\WPBones\\\\' => []];\n");
    $this->write('plugin/Notes.php', "<?php\n// Forked from WPKirk, the boilerplate.\n");

    $run = $this->bones->run(['rename', '--update']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertSame(
      "<?php\nnamespace ProbePlugin\\WPBones\\Foundation;\n\$file = 'probe-plugin.php';\n",
      $this->read('vendor/wpbones/wpbones/src/Foundation/Plugin.php')
    );
    $this->assertStringContainsString('ProbePlugin\\\\WPBones', $this->read('vendor/composer/autoload_psr4.php'));
    $this->assertSame("<?php\n// Forked from WPKirk, the boilerplate.\n", $this->read('plugin/Notes.php'));
    $this->assertSame('Probe Plugin,ProbePlugin', $this->read('namespace'));
  }

  public function test_update_survives_a_symlink_loop_in_vendor(): void
  {
    $this->renamed("<?php\n/**\n * Plugin Name: Probe Plugin\n */\n");
    $this->write('vendor/wpbones/wpbones/src/Foundation/Plugin.php', "<?php\nnamespace WPKirk\\WPBones\\Foundation;\n");
    symlink('..', $this->bones->plugin . '/vendor/wpbones/wpbones/src/loop');

    $run = $this->bones->run(['rename', '--update'], '', 20);

    $this->assertFalse($run['timedOut'], 'rename --update never came back');
    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString('ProbePlugin\\WPBones', $this->read('vendor/wpbones/wpbones/src/Foundation/Plugin.php'));
  }

  public function test_reset_renames_the_language_files_back(): void
  {
    $this->renamed("<?php\n/**\n * Plugin Name: Probe Plugin\n * Domain Path: /languages\n */\n");
    $this->write('languages/probe-plugin-it_IT.po', "msgid \"\"\nmsgstr \"X-Domain: probe-plugin\\n\"\n");

    $run = $this->bones->run(['rename', '--reset']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertFileExists($this->bones->plugin . '/wp-kirk.php');
    $this->assertStringContainsString('X-Domain: wp-kirk', $this->read('languages/wp-kirk-it_IT.po'));
  }

  /** A plugin that went through `rename "Probe Plugin"` already. */
  private function renamed(string $main): void
  {
    unlink($this->bones->plugin . '/wp-kirk.php');
    $this->write('probe-plugin.php', $main);
    $this->write('namespace', 'Probe Plugin,ProbePlugin');
  }

  private function write(string $file, string $content): void
  {
    $path = $this->bones->plugin . '/' . $file;

    if (!is_dir(dirname($path))) {
      mkdir(dirname($path), 0777, true);
    }

    file_put_contents($path, $content);
  }

  private function read(string $file): string
  {
    return (string) file_get_contents($this->bones->plugin . '/' . $file);
  }
}
