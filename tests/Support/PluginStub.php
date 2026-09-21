<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Support;

/**
 * The smallest stand-in for the plugin container that View and the asset enqueuers
 * actually read: a base path on disk, the three public asset URLs and the text domain.
 *
 * Add a property when a test needs one, never speculatively.
 */
final class PluginStub
{
  public string $basePath;

  public string $apps = 'https://example.test/wp-content/plugins/stub/public/apps';

  public string $css = 'https://example.test/wp-content/plugins/stub/public/css';

  public string $js = 'https://example.test/wp-content/plugins/stub/public/js';

  public string $TextDomain = 'stub';

  public string $DomainPath = 'languages';

  public function __construct(?string $basePath = null)
  {
    $this->basePath = $basePath ?? sys_get_temp_dir() . '/wpbones-tests-' . bin2hex(random_bytes(6));

    if (!is_dir($this->basePath . '/resources/views')) {
      mkdir($this->basePath . '/resources/views', 0777, true);
    }
  }

  public function isAjax(): bool
  {
    return false;
  }

  /**
   * Remove the throwaway base path. View's constructor creates a `.cache` directory
   * under it for BladeOne, so a test that forgets this leaves a tree behind on every
   * run — 32 of them after one suite, the first time this stub was used.
   */
  public function cleanup(): void
  {
    if (!is_dir($this->basePath) || strpos($this->basePath, sys_get_temp_dir()) !== 0) {
      return;
    }

    $entries = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($this->basePath, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($entries as $entry) {
      $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($this->basePath);
  }
}
