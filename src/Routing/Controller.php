<?php

namespace Ondapresswp\WPBones\Routing;

use Ondapresswp\WPBones\Foundation\Http\Request;
use Ondapresswp\WPBones\Support\Traits\HasAttributes;
use Ondapresswp\WPBones\View\View;

if (!defined('ABSPATH')) {
  exit();
}

abstract class Controller
{
  use HasAttributes;

  private $_request = null;

  /**
   * This method is executed by add_action( 'load-{}' )
   *
   */
  public function load()
  {
  }

  /**
   * Redirect the browser to a location.
   * If the header has been sent, then a Javascript and meta refresh will be inserted
   * into the page.
   *
   * @param string $location
   */
  public function redirect(string $location = '')
  {
    $args = array_filter(array_keys($_REQUEST), function ($e) {
      return $e !== 'page';
    });

    if (empty($location)) {
      $location = remove_query_arg($args);
    }

    if (headers_sent()) {
      echo '<script type="text/javascript">';
      echo 'window.location.href="' . $location . '";';
      echo '</script>';
      echo '<noscript>';
      echo '<meta http-equiv="refresh" content="0;url=' . $location . '" />';
      echo '</noscript>';
      exit();
    }

    wp_redirect($location);
    exit();
  }

  /**
   * Used to display a view from a menu. The method is usually `index` or `store`. These can return a view instance.
   *
   * @param $method
   * @return string
   */
  public function render($method): ?string
  {
    $view = $this->{$method}();

    if ($view instanceof View) {
      return $view->render();
    }

    return null;
  }

  /**
   * Get the Request instance.
   *
   * @return \Ondapresswp\WPBones\Foundation\Http\Request
   */
  public function getRequestAttribute(): Request
  {
    if (is_null($this->_request)) {
      $this->_request = new Request();
    }

    return $this->_request;
  }
}
