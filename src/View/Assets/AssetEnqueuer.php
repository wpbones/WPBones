<?php

namespace WPKirk\WPBones\View\Assets;

/**
 * AssetEnqueuer abstract class
 *
 * Abstract base class responsible for enqueueing assets (scripts and styles) in WordPress.
 * This class handles the actual WordPress enqueue operations using the data from AssetManager.
 * Concrete implementations should define the specific paths for scripts and styles.
 *
 * @package WPKirk\WPBones\View\Assets
 */
abstract class AssetEnqueuer
{
  /**
   * Plugin container instance.
   *
   * @var mixed
   */
  protected $container;

  /**
   * Asset manager instance.
   *
   * @var AssetManager
   */
  protected AssetManager $assetManager;

  /**
   * AssetEnqueuer constructor.
   *
   * @param mixed        $container    The plugin container instance.
   * @param AssetManager $assetManager The asset manager instance.
   */
  public function __construct($container, AssetManager $assetManager)
  {
    $this->container = $container;
    $this->assetManager = $assetManager;
  }

  /**
   * Get the base path for scripts.
   *
   * @return string The base URL path for scripts.
   */
  abstract protected function getScriptBasePath(): string;

  /**
   * Get the base path for styles.
   *
   * @return string The base URL path for styles.
   */
  abstract protected function getStyleBasePath(): string;

  /**
   * Enqueue all scripts from the asset manager.
   *
   * @return void
   */
  public function enqueueScripts(): void
  {
    if (!$this->assetManager->hasScripts()) {
      return;
    }

    foreach ($this->assetManager->getScripts() as $script) {
      $this->enqueueScript($script);
    }

    $this->enqueueLocalizeScripts();
    $this->enqueueInlineScripts();
  }

  /**
   * Enqueue all styles from the asset manager.
   *
   * @return void
   */
  public function enqueueStyles(): void
  {
    if (!$this->assetManager->hasStyles()) {
      return;
    }

    foreach ($this->assetManager->getStyles() as $style) {
      $this->enqueueStyle($style);
    }

    $this->enqueueInlineStyles();
  }

  /**
   * Enqueue a single script.
   *
   * @param array $script The script data array.
   *
   * @return void
   */
  protected function enqueueScript(array $script): void
  {
    $src = $this->getScriptBasePath() . '/' . $script['name'] . '.js';

    wp_enqueue_script(
      $script['name'],
      $src,
      $script['deps'] ?? [],
      $script['ver'] ?? false,
      $script['args'] ?? true
    );
  }

  /**
   * Enqueue a single style.
   *
   * @param array $style The style data array.
   *
   * @return void
   */
  protected function enqueueStyle(array $style): void
  {
    $src = $this->getStyleBasePath() . '/' . $style['name'] . '.css';

    wp_enqueue_style(
      $style['name'],
      $src,
      $style['deps'] ?? [],
      $style['ver'] ?? false,
      $style['media'] ?? 'all'
    );
  }

  /**
   * Enqueue all localize scripts.
   *
   * @return void
   */
  protected function enqueueLocalizeScripts(): void
  {
    if (!$this->assetManager->hasLocalizeScripts()) {
      return;
    }

    foreach ($this->assetManager->getLocalizeScripts() as $script) {
      wp_localize_script(
        $script['handle'],
        $script['name'],
        $script['data']
      );
    }
  }

  /**
   * Enqueue all inline scripts.
   *
   * @return void
   */
  protected function enqueueInlineScripts(): void
  {
    if (!$this->assetManager->hasInlineScripts()) {
      return;
    }

    foreach ($this->assetManager->getInlineScripts() as $script) {
      wp_add_inline_script(
        $script['name'],
        $script['data'],
        $script['position'] ?? 'after'
      );
    }
  }

  /**
   * Enqueue all inline styles.
   *
   * @return void
   */
  protected function enqueueInlineStyles(): void
  {
    if (!$this->assetManager->hasInlineStyles()) {
      return;
    }

    foreach ($this->assetManager->getInlineStyles() as $style) {
      wp_add_inline_style(
        $style['name'],
        $style['data']
      );
    }
  }

  /**
   * Enqueue all assets (scripts and styles).
   *
   * @return void
   */
  public function enqueue(): void
  {
    $this->enqueueScripts();
    $this->enqueueStyles();
  }
}
