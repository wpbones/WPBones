<?php

namespace WPKirk\WPBones\View\Assets;

/**
 * FrontendAssetEnqueuer class
 *
 * Concrete implementation of AssetEnqueuer for the WordPress frontend/theme area.
 * Handles enqueueing of scripts and styles specifically for the public-facing site.
 *
 * @package WPKirk\WPBones\View\Assets
 */
class FrontendAssetEnqueuer extends AssetEnqueuer
{
  /**
   * Get the base path for frontend scripts.
   *
   * Returns the URL path to the JavaScript files directory for the frontend.
   *
   * @return string The base URL path for frontend scripts.
   */
  protected function getScriptBasePath(): string
  {
    return $this->container->js;
  }

  /**
   * Get the base path for frontend styles.
   *
   * Returns the URL path to the CSS files directory for the frontend.
   *
   * @return string The base URL path for frontend styles.
   */
  protected function getStyleBasePath(): string
  {
    return $this->container->css;
  }
}
