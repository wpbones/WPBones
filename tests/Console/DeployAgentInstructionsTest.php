<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\BonesProcess;

/**
 * A coding agent's instruction files shipped with every deploy: the default skip list named
 * README.md and the Dockerfile but no CLAUDE.md or AGENTS.md, and a web server hands out a
 * plugin's files as they are, so a plugin's CLAUDE.md (architecture notes, known weaknesses,
 * release steps) could be read under wp-content/plugins/ on every site that installed it.
 *
 * Since 3.3.0 the deploy leaves them out wherever they are, vendor/ included. Their folders
 * (.claude/, .codex/, ...) never shipped: the deploy copies no dot entry.
 */
#[Group('console')]
final class DeployAgentInstructionsTest extends TestCase
{
  private BonesProcess $bones;

  private string $target = '';

  protected function setUp(): void
  {
    parent::setUp();

    $this->bones = new BonesProcess();
    $this->target = $this->bones->root . '/out';

    foreach (
      [
        'AGENTS.md' => "# internal notes\n",
        'AGENTS.override.md' => "# internal notes\n",
        'AGENT.md' => "# internal notes\n",
        'CLAUDE.local.md' => "# internal notes\n",
        'GEMINI.md' => "# internal notes\n",
        'plugin/Providers/CLAUDE.md' => "# internal notes\n",
        'plugin/Providers/AgentsServiceProvider.php' => "<?php\n// runtime code\n",
        'resources/assets/apps/AGENTS.md' => "# internal notes\n",
        'resources/assets/apps/agents-panel.tsx' => "export {};\n",
        'vendor/acme/lib/AGENTS.md' => "# internal notes\n",
        'vendor/acme/lib/functions.php' => "<?php\n",
        'docs/agents.md' => "# the plugin's agents feature\n",
        '.claude/settings.json' => "{}\n",
        '.codex/config.toml' => "\n",
      ]
      as $file => $content
    ) {
      $path = $this->bones->plugin . '/' . $file;

      if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
      }

      file_put_contents($path, $content);
    }

    // The usual way to keep one copy of the instructions: CLAUDE.md pointing at AGENTS.md.
    symlink('AGENTS.md', $this->bones->plugin . '/CLAUDE.md');
  }

  protected function tearDown(): void
  {
    $this->bones->remove();

    parent::tearDown();
  }

  /** @return array{status:int, output:string} */
  private function deploy(string ...$options): array
  {
    return $this->bones->run(['deploy', $this->target, '--no-build', ...$options]);
  }

  /** @return string[] Every file and link in the package, relative to it. */
  private function packaged(): array
  {
    $files = [];
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($this->target, \FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $item) {
      $files[] = substr($item->getPathname(), strlen($this->target) + 1);
    }

    sort($files);

    return $files;
  }

  public function test_no_agent_instructions_reach_the_package(): void
  {
    $result = $this->deploy();

    $this->assertSame(0, $result['status'], $result['output']);
    $this->assertFileExists($this->target . '/wp-kirk.php', 'the deploy did not run');
    $this->assertFileDoesNotExist($this->target . '/AGENTS.md');
    $this->assertFileDoesNotExist($this->target . '/AGENTS.override.md');
    $this->assertFileDoesNotExist($this->target . '/AGENT.md');
    $this->assertFileDoesNotExist($this->target . '/CLAUDE.local.md');
    $this->assertFileDoesNotExist($this->target . '/GEMINI.md');
    $this->assertFileDoesNotExist($this->target . '/plugin/Providers/CLAUDE.md');
    $this->assertFileDoesNotExist($this->target . '/vendor/acme/lib/AGENTS.md');
  }

  public function test_a_claude_md_link_does_not_reach_the_package(): void
  {
    $result = $this->deploy();

    $this->assertSame(0, $result['status'], $result['output']);
    // assertFileDoesNotExist follows the link, which points at a file the deploy left out too.
    $this->assertFalse(is_link($this->target . '/CLAUDE.md'), 'CLAUDE.md shipped as a link');
    $this->assertFileDoesNotExist($this->target . '/CLAUDE.md');
  }

  public function test_none_reach_a_wordpress_org_package(): void
  {
    $result = $this->deploy('--wp');

    $this->assertSame(0, $result['status'], $result['output']);
    $this->assertFileExists(
      $this->target . '/resources/assets/apps/agents-panel.tsx',
      '--wp did not keep resources/assets',
    );
    $this->assertFileDoesNotExist($this->target . '/resources/assets/apps/AGENTS.md');
    $this->assertFileDoesNotExist($this->target . '/AGENTS.md');
  }

  public function test_their_folders_never_reached_the_package(): void
  {
    $result = $this->deploy();

    $this->assertSame(0, $result['status'], $result['output']);
    $this->assertDirectoryDoesNotExist($this->target . '/.claude');
    $this->assertDirectoryDoesNotExist($this->target . '/.codex');
  }

  public function test_everything_else_still_ships(): void
  {
    // --wp, which keeps resources/assets, so the script beside the AGENTS.md there is checked too.
    $result = $this->deploy('--wp');

    $this->assertSame(0, $result['status'], $result['output']);
    $this->assertSame(
      [
        'docs/agents.md',
        'plugin/Providers/AgentsServiceProvider.php',
        'readme.txt',
        'resources/assets/apps/agents-panel.tsx',
        'vendor/acme/lib/functions.php',
        'wp-kirk.php',
      ],
      $this->packaged(),
    );
  }
}
