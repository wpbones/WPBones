<?php

namespace WPKirk\WPBones\View\Assets;

/**
 * AssetManager class
 *
 * Manages collections of assets (scripts and styles) to be enqueued.
 * This class is responsible for storing and organizing assets without
 * directly interacting with WordPress enqueue functions.
 *
 * @package WPKirk\WPBones\View\Assets
 */
class AssetManager
{
  /**
   * Collection of scripts to enqueue.
   *
   * @var array
   */
  protected array $scripts = [];

  /**
   * Collection of styles to enqueue.
   *
   * @var array
   */
  protected array $styles = [];

  /**
   * Collection of scripts to localize.
   *
   * @var array
   */
  protected array $localizeScripts = [];

  /**
   * Collection of inline scripts.
   *
   * @var array
   */
  protected array $inlineScripts = [];

  /**
   * Collection of inline styles.
   *
   * @var array
   */
  protected array $inlineStyles = [];

  /**
   * Add a script to the collection.
   *
   * @param string           $name The script handle/name.
   * @param array            $deps Optional. Array of script dependencies. Default empty array.
   * @param string|bool|null $ver  Optional. Script version number. Default false.
   * @param bool|array       $args Optional. Additional arguments for script loading. Default true.
   *
   * @return $this
   */
  public function addScript(string $name, array $deps = [], $ver = false, $args = true): self
  {
    $this->scripts[] = compact('name', 'deps', 'ver', 'args');

    return $this;
  }

  /**
   * Add a style to the collection.
   *
   * @param string           $name  The style handle/name.
   * @param array            $deps  Optional. Array of style dependencies. Default empty array.
   * @param string|bool|null $ver   Optional. Style version number. Default false.
   * @param string           $media Optional. The media for which this stylesheet has been defined. Default 'all'.
   *
   * @return $this
   */
  public function addStyle(string $name, array $deps = [], $ver = false, string $media = 'all'): self
  {
    $this->styles[] = compact('name', 'deps', 'ver', 'media');

    return $this;
  }

  /**
   * Add a script localization to the collection.
   *
   * @param string $handle The script handle to attach data to.
   * @param string $name   The name of the JavaScript object.
   * @param array  $data   The data to localize.
   *
   * @return $this
   */
  public function addLocalizeScript(string $handle, string $name, array $data): self
  {
    $this->localizeScripts[] = compact('handle', 'name', 'data');

    return $this;
  }

  /**
   * Add an inline script to the collection.
   *
   * @param string $name     The script handle to attach inline script to.
   * @param string $data     The inline script data.
   * @param string $position Optional. Whether to add the inline script before or after. Default 'after'.
   *
   * @return $this
   */
  public function addInlineScript(string $name, string $data, string $position = 'after'): self
  {
    $this->inlineScripts[] = compact('name', 'data', 'position');

    return $this;
  }

  /**
   * Add an inline style to the collection.
   *
   * @param string $name The style handle to attach inline style to.
   * @param string $data The inline style data.
   *
   * @return $this
   */
  public function addInlineStyle(string $name, string $data): self
  {
    $this->inlineStyles[] = compact('name', 'data');

    return $this;
  }

  /**
   * Add an apps script (React bundle) to the collection.
   *
   * @param string $name     The script handle/name.
   * @param array  $deps     Optional. Array of script dependencies. Default empty array.
   * @param string $ver      Optional. Script version number. Default empty string.
   * @param array  $args     Optional. Additional arguments for script loading. Default empty array.
   *
   * @return $this
   */
  public function addAppsScript(string $name, array $deps = [], string $ver = '', array $args = []): self
  {
    $this->scripts[] = array_merge(compact('name', 'deps', 'ver'), ['args' => $args, 'isApp' => true]);

    return $this;
  }

  /**
   * Add an apps style to the collection.
   *
   * @param string $name The style handle/name.
   * @param array  $deps Optional. Array of style dependencies. Default empty array.
   * @param string $ver  Optional. Style version number. Default empty string.
   *
   * @return $this
   */
  public function addAppsStyle(string $name, array $deps = [], string $ver = ''): self
  {
    $this->styles[] = array_merge(compact('name', 'deps', 'ver'), ['media' => 'all', 'isApp' => true]);

    return $this;
  }

  /**
   * Get all scripts in the collection.
   *
   * @return array
   */
  public function getScripts(): array
  {
    return $this->scripts;
  }

  /**
   * Get all styles in the collection.
   *
   * @return array
   */
  public function getStyles(): array
  {
    return $this->styles;
  }

  /**
   * Get all localize scripts in the collection.
   *
   * @return array
   */
  public function getLocalizeScripts(): array
  {
    return $this->localizeScripts;
  }

  /**
   * Get all inline scripts in the collection.
   *
   * @return array
   */
  public function getInlineScripts(): array
  {
    return $this->inlineScripts;
  }

  /**
   * Get all inline styles in the collection.
   *
   * @return array
   */
  public function getInlineStyles(): array
  {
    return $this->inlineStyles;
  }

  /**
   * Check if there are any scripts in the collection.
   *
   * @return bool
   */
  public function hasScripts(): bool
  {
    return !empty($this->scripts);
  }

  /**
   * Check if there are any styles in the collection.
   *
   * @return bool
   */
  public function hasStyles(): bool
  {
    return !empty($this->styles);
  }

  /**
   * Check if there are any localize scripts in the collection.
   *
   * @return bool
   */
  public function hasLocalizeScripts(): bool
  {
    return !empty($this->localizeScripts);
  }

  /**
   * Check if there are any inline scripts in the collection.
   *
   * @return bool
   */
  public function hasInlineScripts(): bool
  {
    return !empty($this->inlineScripts);
  }

  /**
   * Check if there are any inline styles in the collection.
   *
   * @return bool
   */
  public function hasInlineStyles(): bool
  {
    return !empty($this->inlineStyles);
  }

  /**
   * Clear all assets from the collection.
   *
   * @return $this
   */
  public function clear(): self
  {
    $this->scripts = [];
    $this->styles = [];
    $this->localizeScripts = [];
    $this->inlineScripts = [];
    $this->inlineStyles = [];

    return $this;
  }
}
