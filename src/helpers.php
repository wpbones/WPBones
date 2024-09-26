<?php

use WPKirk\WPBones\Support\Str;

if (!defined('ABSPATH')) {
  exit();
}

if (!function_exists('wpbones_value')) {
  /**
   * Return the default value of the given value.
   *
   * @param mixed $value
   *
   * @return mixed
   */
  function wpbones_value($value)
  {
    return $value instanceof Closure ? $value() : $value;
  }
}

if (!function_exists('wpbones_env')) {
  /**
   * Gets the value of an environment variable. Supports boolean, empty and null.
   *
   * @param string $key
   * @param mixed  $default
   *
   * @return mixed
   */
  function wpbones_env($key, $default = null)
  {
    $value = getenv($key);

    if ($value === false) {
      return wpbones_value($default);
    }

    switch (strtolower($value)) {
      case 'true':
      case '(true)':
        return true;

      case 'false':
      case '(false)':
        return false;

      case 'empty':
      case '(empty)':
        return '';

      case 'null':
      case '(null)':
        return;
    }

    if (Str::startsWith($value, '"') && Str::endsWith($value, '"')) {
      return substr($value, 1, -1);
    }

    return $value;
  }
}

if (!function_exists('wpbones_array_insert')) {
  /**
   * Insert a key => value into a second array to a specify index
   *
   * @brief Insert a key value pairs in array
   *
   * @param array  $arr   Source array
   * @param string $key   Key
   * @param mixed  $val   Value
   * @param int    $index Optional. Index zero base
   *
   * @return array
   */
  function wpbones_array_insert($arr, $key, $val, $index = 0): array
  {
    $arrayEnd = array_splice($arr, $index);
    $arrayStart = array_splice($arr, 0, $index);

    return array_merge($arrayStart, [$key => $val], $arrayEnd);
  }
}

if (!function_exists('wpbones_array_assoc_default')) {
  /**
   * Return a new associative array with $default values +/- $merge values.
   *
   * @param array $default The default array values.
   * @param array $merge   An associate (+) or key values (-) array. For example, if you'll use [ 'foo' ] the 'foo' will
   *                       be added to $default array. If you'll use [ 'foo', 'bar' => false ], then 'foo' will be added
   *                       and 'bar' will be removed.
   *
   * @return array
   */
  function wpbones_array_assoc_default($default, $merge): array
  {
    $add = [];
    $del = [];

    foreach ($merge as $key => $value) {
      if (is_numeric($key) && !is_bool($value)) {
        $add[] = $value;
      } elseif (!is_numeric($key) && is_bool($value) && $value === false) {
        $del[] = $key;
      }
    }

    $result = array_unique(array_merge(array_diff($default, $del), $add));

    return $result;
  }
}

if (!function_exists('wpbones_checked')) {
  /**
   * Commodity to extends checked() WordPress function with array check
   *
   * @param string|array $haystack Single value or array
   * @param mixed        $current  (true) The other value to compare if not just true
   * @param bool         $echo     Whether to echo or just return the string
   *
   * @return string html attribute or empty string
   */
  function wpbones_checked($haystack, $current, $echo = true)
  {
    if (is_array($haystack)) {
      if (in_array($current, $haystack)) {
        $current = $haystack = 1;

        return checked($haystack, $current, $echo);
      }

      return false;
    }

    return checked($haystack, $current, $echo);
  }
}

if (!function_exists('wpbones_selected')) {
  /**
   * Commodity to extends selected() WordPress function with array check
   *
   * @param string|array $haystack Single value or array
   * @param mixed        $current  (true) The other value to compare if not just true
   * @param bool         $echo     Whether to echo or just return the string
   *
   * @return string html attribute or empty string
   */
  function wpbones_selected($haystack, $current, $echo = true)
  {
    if (is_array($haystack)) {
      if (in_array($current, $haystack)) {
        $current = $haystack = 1;

        return selected($haystack, $current, $echo);
      }

      return false;
    }

    return selected($haystack, $current, $echo);
  }
}

if (!function_exists('wpbones_is_true')) {
  /**
   * Utility to check if a value is true.
   *
   * @param mixed $value String, boolean or integer.
   *
   * @return bool
   */
  function wpbones_is_true($value): bool
  {
    return !in_array(strtolower($value), ['', 'false', '0', 'no', 'n', 'off', null]);
  }
}

if (!function_exists('wpbones_logger')) {
  /* Utility to get an instance of Logger. */
  function wpbones_logger()
  {
    if (count(func_get_args()) > 0) {
      return call_user_func_array([WPKirk()->log(), 'debug'], func_get_args());
    }

    return WPKirk()->log();
  }
}

if (!function_exists('logger')) {
  /* Utility to get an instance of Logger. */
  function logger()
  {
    return call_user_func_array('wpbones_logger', func_get_args());
  }
}

if (!function_exists('wpbones_provider')) {
  /**
   * Return a provider by name
   *
   * @param string  $name The Class name of the provider.
   *
   * @since 1.6.0
   *
   * @return mixed|null
   */
  function wpbones_provider($provider)
  {
    return WPKirk()->provider($provider);
  }
}

if (!function_exists('wpbones_flatten_and_uniquify')) {
  /**
   * Flattens a multi-dimensional array or comma-separated string and removes duplicates.
   *
   * This function takes an input which can be either a multi-dimensional array
   * or a comma-separated string, flattens it into a single-dimensional array,
   * and removes any duplicate values.
   *
   * @param array|string $input The input to be flattened and uniquified.
   *                            Can be a multi-dimensional array or a comma-separated string.
   *
   * @since 1.6.1

   * @return array A flat array with unique values.
   */
  function wpbones_flatten_and_uniquify($input): array
  {
    // If the input is a string, convert it to an array
    if (is_string($input)) {
      $input = explode(',', $input);
    }

    // Use array_reduce to recursively flatten the array
    $flattened = array_reduce((array)$input, function ($carry, $item) {
      return array_merge($carry, is_array($item) ? wpbones_flatten_and_uniquify($item) : [$item]);
    }, []);

    // Remove duplicates and return the array
    return array_unique($flattened);
  }
}
