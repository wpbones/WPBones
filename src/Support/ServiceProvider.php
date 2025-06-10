<?php

namespace Ondapresswp\WPBones\Support;

abstract class ServiceProvider
{
  /**
   * Register the service provider.
   *
   * @return void
   */
  abstract public function register();

  /**
   * Instance of main plugin.
   *
   * @var
   */
  protected $plugin;

  public function __construct($plugin)
  {
    $this->plugin = $plugin;
  }

  /**
   * Dynamically handle missing method calls.
   *
   * @param string $method
   * @param array  $parameters
   */
  public function __call(string $method, $parameters)
  {
    if ($method == 'boot') {
      return;
    }
  }

  /**
   * Return an array of key => value properties.
   *
   * @param array $properties
   *
   * @example
   *
   * ```php
   * $properties = [
   *  // menu_icon will be the array key
   *  // menuIcon is the property or a method of this class. Must be not null
   *   'menu_icon' => 'menuIcon',
   * ];
   * ```
   *
   *
   * @return array
   */
  protected function mapPropertiesToArray($properties): array
  {
    $result = [];

    foreach ($properties as $key => $prop) {

      if ($prop === 'register') {
        throw new \Exception('The property name "register" is reserved.');
      }

      if (method_exists($this, $prop)) {
        $value = $this->$prop();
      } elseif (property_exists($this, $prop)) {
        $value = $this->$prop;
      }

      if (!is_null($value)) {
        $result[$key] = $value;
      }
    }

    return $result;
  }
}
