<?php

namespace Ondapresswp\WPBones\Routing\API;

if (!defined('ABSPATH')) {
  exit();
}

use WP_Error;
use Ondapresswp\WPBones\Support\ServiceProvider;

/**
 * This provider is used to register all API routes.
 * We are going to scan the plugin folder /api to search all vendor/version/routes.
 *
 */
class RestProvider extends ServiceProvider
{
  protected $routes = [];

  // default config filename 'api.php'
  private string $configFilename = 'api';

  // register
  public function register()
  {
    $this->initBasicAuth();
    $this->initCustomRoutes();
    $this->initWordPress();
  }

  private function initBasicAuth()
  {
    if (true === $this->config('auth.basic', false)) {
      add_filter('determine_current_user', [$this, 'determine_current_user'], 20);
      add_filter('rest_authentication_errors', [$this, 'rest_authentication_errors']);
    }
  }

  /**
   * Commodiates to get the config value.
   */
  private function config($path, $default = null)
  {
    return $this->plugin->config($this->configFilename . '.' . $path, $default);
  }

  private function initCustomRoutes()
  {
    if (true === $this->config('custom.enabled', false)) {
      $folder = $this->plugin->basePath . $this->config('custom.path', '/api');

      $api_folder_exists = file_exists($folder);

      if ($api_folder_exists) {
        $struct = $this->dirToArray($folder);

        $this->routes = $this->array_flatten($struct);

        foreach ($this->routes as $vendor => $file) {
          Route::vendor($vendor);
          include $folder . '/' . $vendor . '/' . $file;
        }

        Route::register();
      }
    }
  }

  private function dirToArray($dir)
  {
    $contents = [];

    foreach (scandir($dir) as $node) {
      if ($node == '.' || $node == '..' || $node == ".DS_Store") {
        continue;
      }
      if (is_dir($dir . '/' . $node)) {
        $contents[$node] = $this->dirToArray($dir . '/' . $node);
      } else {
        $contents = $node;
      }
    }

    return $contents;
  }

  private function array_flatten($array, $path = ''): array
  {
    $result = [];

    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $new = $this->array_flatten($value, $path . $key . '/');
        $result = array_merge($result, $new);
      } else {
        $result[$path . $key] = $value;
      }
    }

    return $result;
  }

  private function initWordPress()
  {
    if (true === $this->config('wp.require_authentication', false)) {
      add_filter('rest_authentication_errors', function ($result) {
        // If a previous authentication check was applied,
        // pass that result along without modification.
        if (true === $result || is_wp_error($result)) {
          return $result;
        }

        // No authentication has been performed yet.
        // Return an error if user is not logged in.
        if (!is_user_logged_in()) {
          return new WP_Error('rest_not_logged_in', __('You are not currently logged in.'), ['status' => 401]);
        }

        // Our custom authentication check should have no effect
        // on logged-in requests
        return $result;
      });
    }
  }

  public function determine_current_user($user)
  {
    global $wp_json_basic_auth_error;

    $wp_json_basic_auth_error = null;

    // Don't authenticate twice
    if (!empty($user)) {
      return $user;
    }

    // Check that we're trying to authenticate
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
      return $user;
    }

    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];

    /**
     * In multi-site, wp_authenticate_spam_check filter is run on authentication. This filter calls
     * get_currentuserinfo() which in turn calls the determine_current_user filter. This leads to infinite
     * recursion and a stack overflow unless the current function is removed from the determine_current_user
     * filter during authentication.
     */
    remove_filter('determine_current_user', [$this, 'determine_current_user'], 20);

    $user = wp_authenticate($username, $password);

    add_filter('determine_current_user', [$this, 'determine_current_user'], 20);

    if (is_wp_error($user)) {
      $wp_json_basic_auth_error = $user;

      return null;
    }

    $wp_json_basic_auth_error = true;

    return $user->ID;
  }

  public function rest_authentication_errors($error)
  {
    // Pass through other errors
    if (!empty($error)) {
      return $error;
    }

    global $wp_json_basic_auth_error;

    return $wp_json_basic_auth_error;
  }
}
