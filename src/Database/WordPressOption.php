<?php

namespace Ondapresswp\WPBones\Database;

use ArrayAccess;
use ReturnTypeWillChange;
use Ondapresswp\WPBones\Foundation\Plugin;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * @class   WordPressOption
 * @package Ondapresswp\WPBones\Database
 *
 *
 */
class WordPressOption implements ArrayAccess
{
  /**
   * Name of table.
   *
   * @var string
   */
  protected $tableName = '';

  /**
   * Table description fields.
   *
   * @var array
   */
  protected $fields = ['option_id', 'option_name', 'option_value', 'autoload'];

  /**
   * An instance of Plugin class or null.
   *
   * @var Plugin
   */
  protected $plugin = null;

  /**
   * Option record.
   *
   * @var array|null|object
   */
  protected $row;

  /**
   * Decoded json form option_value.
   *
   * @var array
   */
  private $_value = [];

  /**
   * Create a new WordPressOption.
   *
   * @param $plugin
   */
  public function __construct($plugin = null)
  {
    global $wpdb;

    $this->tableName = $wpdb->options;

    if (!is_null($plugin)) {
      $this->plugin = $plugin;

      // in $this->row you'll fond a stdClass with column/property
      $this->row = $wpdb->get_row(
        "SELECT * FROM {$this->tableName} WHERE option_name='{$plugin->slug}'"
      );

      if (is_null($this->row)) {
        $options = include $this->plugin->basePath . '/config/options.php';

        $values = [
          'option_name' => $this->plugin->slug,
          'option_value' => json_encode($options),
        ];
        $result = $wpdb->insert($this->tableName, $values);

        $this->row = $wpdb->get_row(
          "SELECT * FROM {$this->tableName} WHERE option_name='{$plugin->slug}'"
        );
      }

      if (isset($this->row->option_value) && !empty($this->row->option_value)) {
        $this->_value = (array)json_decode($this->row->option_value, true);
      }
    }
  }

  /**
   * Get the string representation (json) of the options.
   *
   * @return string
   */
  public function __toString()
  {
    return json_encode($this->_value, JSON_PRETTY_PRINT);
  }

  /**
   * Return the flat array of the options.
   *
   * @return array
   */
  public function toArray(): array
  {
    return $this->_value;
  }

  public function offsetSet($offset, $value): void
  {
    $this->set($offset, $value);
  }

  /**
   * Set (or remove) a branch/single option by path.
   *
   * @param string $path The path of the option.
   * @param mixed $value Optional. value to set, if null the option will be removed.
   *
   * @return mixed
   */
  public function set($path, $value = null)
  {
    if (is_null($value)) {
      //return $this->delete($path);
    }

    $path = str_replace('/', '.', $path);
    $keys = explode('.', $path);

    $copy = $this->_value;

    $array = &$copy;

    foreach ($keys as $key) {
      if (!isset($array[$key])) {
        $array[$key] = '';
      }

      $array = &$array[$key];
    }

    $array = $value;

    $this->update($copy);

    return $value;
  }

  /**
   * Update a branch of options.
   *
   * @param array $options
   *
   * @return false|int
   */
  public function update($options = [])
  {
    global $wpdb;

    if (is_null($this->row)) {
      return $this->reset();
    }

    $mergeOptions = array_replace_recursive($this->_value, $options);

    $values = [
      'option_value' => json_encode($mergeOptions),
    ];

    $result = $wpdb->update($this->tableName, $values, [
      'option_name' => $this->plugin->slug,
    ]);

    $this->_value = (array)json_decode($values['option_value'], true);

    return $result;
  }

  /**
   * Load the default value from `config/options.php` and replace the current.
   *
   * @return false|int
   */
  public function reset()
  {
    global $wpdb;

    $options = include $this->plugin->basePath . '/config/options.php';

    $values = [
      'option_name' => $this->plugin->slug,
      'option_value' => json_encode($options),
    ];

    $result = $wpdb->update($this->tableName, $values, [
      'option_name' => $this->plugin->slug,
    ]);

    $this->_value = (array)json_decode($values['option_value'], true);

    return $result;
  }

  /**
   * Delete a branch/single option by path.
   *
   * @param string $path The path of the option to delete.
   *
   * @return mixed
   */
  public function delete($path = '')
  {
    global $wpdb;

    if (empty($path)) {
      $this->_value = [];
    } else {
      $path = str_replace('/', '.', $path);
      $keys = explode('.', $path);

      $lastKey = $keys[count($keys) - 1];

      $array = &$this->_value;

      foreach ($keys as $key) {
        if ($key == $lastKey) {
          unset($array[$key]);
          break;
        }
        $array = &$array[$key];
      }
    }

    $values = [
      'option_value' => json_encode($this->_value),
    ];

    $result = $wpdb->update($this->tableName, $values, [
      'option_name' => $this->plugin->slug,
    ]);

    return $this->_value;
  }

  public function offsetExists($offset): bool
  {
    return (bool)!is_null($this->get($offset, null));
  }

  /**
   * Return a branch/single option by path.
   *
   * @param string $path The path option.
   * @param string $default Optional. A default value if the option doesn't exists.
   *
   * @return mixed
   * @example
   *
   *     echo $plugin->options->get( 'General.doNotExists', 'default');
   *
   */
  public function get($path, $default = '')
  {
    $path = str_replace('/', '.', $path);
    $keys = explode('.', $path);

    $current = $this->_value;

    foreach ($keys as $key) {
      // check if the $key exists into the array
      if (!array_key_exists($key, $current)) {
        return $default;
      }

      if (!isset($current[$key]) && !is_null($current[$key])) {
        return $default;
      }

      if (is_object($current[$key])) {
        $current = (array)$current[$key];
      } else {
        $current = $current[$key];
      }
    }

    return $current;
  }

  public function offsetUnset($offset): void
  {
    $this->set($offset);
  }

  #[ReturnTypeWillChange]
  public function offsetGet($offset)
  {
    return $this->get($offset);
  }

  /**
   * Execute a delta from the current version of the options and the previous version stored in the database.
   *
   * @return false|int
   */
  public function delta()
  {
    global $wpdb;

    if (is_null($this->row)) {
      return $this->reset();
    }

    $options = include $this->plugin->basePath . '/config/options.php';

    $mergeOptions = $this->__delta($options, $this->_value);

    $values = [
      'option_value' => json_encode($mergeOptions),
    ];

    $result = $wpdb->update($this->tableName, $values, [
      'option_name' => $this->plugin->slug,
    ]);

    $this->_value = (array)json_decode($values['option_value'], true);

    return $result;
  }

  /**
   * Do a merge/combine between two object tree.
   * If the old version not contains an object or property, that is added.
   * If the old version contains an object or property less in last version, that it will be deleted.
   *
   * @param mixed $lastVersion Object tree with new or delete object/value
   * @param mixed $result Current Object tree, loaded from serialize or database for example
   *
   * @return Object the delta Object tree
   */
  private function __delta(array $lastVersion, &$result)
  {
    // search for new
    foreach ($lastVersion as $key => $value) {
      if (!is_numeric($key) && !isset($result[$key])) {
        $result[$key] = $value;
      }

      if (is_array($value) && !is_numeric($key)) {
        $result[$key] = $this->__delta($lastVersion[$key], $result[$key]);
      }
    }

    // search for delete
    foreach ($result as $key => $value) {
      if (!is_numeric($key) && !array_key_exists($key, $lastVersion)) {
        unset($result[$key]);
      }
    }

    return $result;
  }
}
