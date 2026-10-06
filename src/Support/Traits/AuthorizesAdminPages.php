<?php

namespace WPKirk\WPBones\Support\Traits;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The pages of config/routes.php and of the pages/ folder are written straight into
 * $_registered_pages, with no menu entry, and WordPress lets any logged-in user open a
 * page registered that way. This checks the capability the page asks for instead.
 */
trait AuthorizesAdminPages
{
  /**
   * Refuse the page to a user without the capability: on load, before any other load
   * callback, and again when it renders, in case the load callbacks were removed.
   *
   * @param string $hookName   The page hook, as get_plugin_page_hookname() returns it.
   * @param mixed  $capability The capability the page asks for; `manage_options` when empty
   *                           (it was `read` up to 2.x).
   */
  protected function authorizeAdminPage(string $hookName, $capability): void
  {
    if (!is_string($capability) || $capability === '') {
      // A cast would ask for `Array`, which no one has, or for `1`, a user level.
      if (!empty($capability)) {
        _doing_it_wrong(
          __METHOD__,
          sprintf('The capability of the WP Bones admin page %s is not a string, so the page asks for manage_options.', $hookName),
          ''
        );
      }

      $capability = 'manage_options';
    }

    $authorize = function () use ($capability) {
      if (!current_user_can($capability)) {
        // As wp-admin/includes/menu.php refuses a page: multisite hangs a splash on the action.
        do_action('admin_page_access_denied');

        wp_die(__('Sorry, you are not allowed to access this page.'), 403);
      }
    };

    add_action("load-{$hookName}", $authorize, PHP_INT_MIN);
    add_action($hookName, $authorize, PHP_INT_MIN);
  }
}
