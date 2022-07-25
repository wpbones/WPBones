<?php

namespace WPKirk\WPBones\Support\Traits;

use WPKirk\WPBones\Support\Str;

trait HasAttributes
{
  /**
   * Get the value of an attribute using its mutator.
   *
   * @param string $key
   * @return mixed
   */
  protected function mutateAttribute($key)
  {
    return $this->{'get' . Str::studly($key) . 'Attribute'}();
  }

  /**
   * Set the value of an attribute using its mutator.
   *
   * @param string $key
   * @param mixed  $value
   * @return mixed
   */
  protected function setMutatedAttributeValue($key, $value)
  {
    return $this->{'set' . Str::studly($key) . 'Attribute'}($value);
  }

  /**
   * Return the value of the method `get{Name}Attribute`.
   *
   * @param string $name Usually the protected property name.
   *
   * @return mixed
   */
  public function __get(string $name)
  {
    $method = 'get' . Str::studly($name) . 'Attribute';
    if (method_exists($this, $method)) {
      return $this->{$method}();
    }

    return null;
  }

  /**
   * Set the value of the method `set{Name}Attribute`.
   *
   * @param string $name Usually the protected property name.
   * @param mixed  $value
   */
  public function __set($name, $value)
  {
    $method = 'set' . Str::studly($name) . 'Attribute';
    if (method_exists($this, $method)) {
      $this->{$method}($value);
    }
  }

  /**
   * Extend the __isset method to check for the method `get{Name}Attribute`.
   *
   * @param string $name Usually the protected property name.
   */
  public function __isset($name)
  {
    $method = 'isset' . Str::studly($name) . 'Attribute';
    if (method_exists($this, $method)) {
      $this->{$method}();
    }
  }

  /**
   * Extend the __unset method to check for the method `get{Name}Attribute`.
   *
   * @param string $name Usually the protected property name.
   */
  public function __unset($name)
  {
    $method = 'unset' . Str::studly($name) . 'Attribute';
    if (method_exists($this, $method)) {
      $this->{$method}();
    }
  }

  /**
   * Determine if a get mutator exists for an attribute.
   *
   * @param string $key
   * @return bool
   */
  public function hasGetMutator($key): bool
  {
    return method_exists($this, 'get' . Str::studly($key) . 'Attribute');
  }

  /**
   * Determine if a set mutator exists for an attribute.
   *
   * @param string $key
   * @return bool
   */
  public function hasSetMutator($key): bool
  {
    return method_exists($this, 'set' . Str::studly($key) . 'Attribute');
  }
}
