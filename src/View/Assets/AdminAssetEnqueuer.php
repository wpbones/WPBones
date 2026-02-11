<?php

namespace WPKirk\WPBones\View\Assets;

/**
 * AdminAssetEnqueuer class
 *
 * Concrete implementation of AssetEnqueuer for the WordPress admin area.
 * Handles enqueueing of scripts and styles specifically for the admin dashboard.
 *
 * @package WPKirk\WPBones\View\Assets
 */
class AdminAssetEnqueuer extends AssetEnqueuer
{
  /**
   * Get the base path for admin scripts.
   *
   * Returns the URL path to the JavaScript files directory for admin area.
   *
   * @return string The base URL path for admin scripts.
   */
  protected function getScriptBasePath(): string
  {
    return $this->container->js;
  }

  /**
   * Get the base path for admin styles.
   *
   * Returns the URL path to the CSS files directory for admin area.
   *
   * @return string The base URL path for admin styles.
   */
  protected function getStyleBasePath(): string
  {
    return $this->container->css;
  }
}
