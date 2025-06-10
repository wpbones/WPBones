<?php

namespace Ondapresswp\WPBones\Foundation;

use Ondapresswp\WPBones\Foundation\Http\Request;
use Ondapresswp\WPBones\Support\ServiceProvider;
use Ondapresswp\WPBones\Support\Traits\HasAttributes;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * @class   WordPressAjaxServiceProvider
 * @package Ondapresswp\WPBones\Foundation
 *
 * @property Request $request
 */
abstract class WordPressAjaxServiceProvider extends ServiceProvider
{
  use HasAttributes;

  /**
   * List of the ajax actions executed by both logged and not logged users.
   * Here you will use a methods list.
   *
   * @var array
   */
  protected $trusted = [];

  /**
   * List of the ajax actions executed only by logged-in users.
   * Here you will use a methods list.
   *
   * @var array
   */
  protected $logged = [];

  /**
   * List of the ajax actions executed only by not logged-in user, usually from frontend.
   * Here you will use a methods list.
   *
   * @var array
   */
  protected $notLogged = [];

  /**
   * The capability required to execute the action.
   * Of course, this is only for logged-in users.
   *
   * @var string
   */
  protected $capability = '';

  /**
   * The nonce key used to verify the request.
   *
   * @var string
   */
  protected $nonceKey = 'nonce';

  /**
   * The nonce hash used to verify the request.
   *
   * @var string
   */
  protected $nonceHash = '';

  private $_request = null;

  /**
   * Init the registered Ajax actions.
   *
   * @access private
   *
   */
  public function register()
  {
    // you can override this method to set the properties
    $this->boot();

    foreach ($this->notLogged as $action) {
      add_action('wp_ajax_nopriv_' . $action, [$this, $action]);
    }

    foreach ($this->trusted as $action) {
      add_action('wp_ajax_nopriv_' . $action, [$this, $action]);
      add_action('wp_ajax_' . $action, [$this, $action]);
    }

    foreach ($this->logged as $action) {
      // if $this->capability is not empty, then use it
      if (!empty($this->capability)) {
        if (!current_user_can($this->capability)) {
          add_action('wp_ajax_' . $action, [$this, '_permissionDenied']);
          continue;
        }
      }

      add_action('wp_ajax_' . $action, function () use ($action) {
        if ($this->verifyNonce()) {
          $this->$action();
        }
      });
    }
  }

  /**
   * Use this method to get the POST data.
   *
   * @param array ...$args
   *
   * @return array
   */
  protected function useHTTPPost(...$args)
  {
    $result = [];
    foreach ($args as $arg) {
      $result[] = isset($_POST[$arg]) ? $_POST[$arg] : null;
    }
    return $result;
  }

  /**
   * Use this method to verify the nonce.
   *
   * @return array
   */
  protected function verifyNonce()
  {
    if (!empty($this->nonceKey) && !empty($this->nonceHash)) {
      if (!isset($_POST[$this->nonceKey])) {
        wp_send_json_error(__("You don't have permission to do this. The nonce is missing."), 403);
        return false;
      }

      if (wp_verify_nonce($_POST[$this->nonceKey], $this->nonceHash) === false) {
        wp_send_json_error(__("You don't have permission to do this. The nonce is invalid."), 403);
        return false;
      }
    }
    return true;
  }

  /**
   * Use this method to send an error JSON response.
   *
   * @access private
   *
   */
  public function _permissionDenied()
  {
    wp_send_json_error(__("You don't have permission to do this."), 403);
  }

  /**
   * You may override this method in order to register your own actions and filters.
   *
   * @access private
   */
  public function boot()
  {
    // You may override this method
  }

  /**
   * Get the request attribute.
   *
   * @return Request
   */
  public function getRequestAttribute(): Request
  {
    if (is_null($this->_request)) {
      $this->_request = new Request();
    }

    return $this->_request;
  }
}
