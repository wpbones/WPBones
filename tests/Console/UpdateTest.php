<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\BonesProcess;

/**
 * Up to 2.0.12 `update` deleted vendor/wpbones/wpbones and then ran a full `composer update`
 * (bones CLI audit, M3): with Composer failing, the plugin was left with no framework. Composer
 * replaces the package by itself, so nothing is deleted first any more.
 */
#[Group('console')]
final class UpdateTest extends TestCase
{
  private BonesProcess $bones;

  protected function setUp(): void
  {
    parent::setUp();

    $this->bones = new BonesProcess();

    mkdir($this->bones->plugin . '/vendor/wpbones/wpbones/src', 0777, true);
    file_put_contents($this->bones->plugin . '/vendor/wpbones/wpbones/src/Plugin.php', "<?php\n");
  }

  protected function tearDown(): void
  {
    $this->bones->remove();

    parent::tearDown();
  }

  public function test_a_failing_composer_leaves_the_framework_in_place(): void
  {
    $run = $this->bones->withFakeComposer(2)->run(['update']);

    $this->assertSame(2, $run['status'], $run['output']);
    $this->assertStringContainsString('composer update exited with status 2', $run['stderr']);
    $this->assertFileExists($this->bones->plugin . '/vendor/wpbones/wpbones/src/Plugin.php');
  }

  public function test_it_updates_the_framework_and_its_dependencies_only(): void
  {
    $run = $this->bones->withFakeComposer(0)->run(['update']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertSame(['update wpbones/wpbones --with-dependencies'], $this->bones->composerCalls());
  }
}
