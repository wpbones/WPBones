<?php

namespace WPKirk\WPBones\Routing;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * A WP Bones admin page maps the HTTP verb to store(), update() and destroy() and runs its
 * `load` callbacks for any request, so up to 2.x a form on another site could post into it with
 * the user's cookies (audit S4). Since 3.0 every request to such a page that is not a GET or a
 * HEAD carries the plugin's nonce: $plugin->csrfField() in the form.
 *
 * @since 3.0.0
 */
class Csrf
{
  /**
   * The name of the field the nonce travels in: not _wpnonce, so that a form may keep a
   * wp_nonce_field() of its own beside it.
   */
  const FIELD = '_wpbones_nonce';

  /**
   * Refuse a request that changes something and carries no valid nonce, on load before any other
   * load callback, and again before the page renders.
   *
   * @param string $hookName The page hook.
   * @param string $action   The plugin's nonce action ($plugin->csrfAction()).
   */
  public static function guard(string $hookName, string $action): void
  {
    $verify = function () use ($action) {
      $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

      if (in_array($method, ['GET', 'HEAD'], true)) {
        return;
      }

      // WordPress's own forms that post to the page they are on, each with a nonce of its own:
      // Screen Options (when the plugin does not save the option itself), filesystem credentials.
      foreach (['screenoptionnonce' => 'screen-options-nonce', '_fs_nonce' => 'filesystem-credentials'] as $field => $own) {
        if (isset($_POST[$field]) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$field])), $own)) {
          return;
        }
      }

      // In the form field, in the query string, or, for a fetch() with a JSON body, which fills no
      // $_POST, in the X-WPBones-Nonce header.
      $nonce = $_SERVER['HTTP_X_WPBONES_NONCE'] ?? ($_REQUEST[self::FIELD] ?? '');
      $nonce = is_string($nonce) ? sanitize_text_field(wp_unslash($nonce)) : '';

      if (!wp_verify_nonce($nonce, $action)) {
        // WordPress's own answer to a stale or missing nonce: "The link you followed has expired."
        wp_nonce_ays($action);
      }
    };

    add_action("load-{$hookName}", $verify, PHP_INT_MIN);
    add_action($hookName, $verify, PHP_INT_MIN);
  }

  /**
   * The hidden fields a form posts the nonce in.
   */
  public static function field(string $action): string
  {
    return wp_nonce_field($action, self::FIELD, true, false);
  }
}
