<?php

namespace WPKirk\WPBones\Routing;

if (!defined('ABSPATH')) {
  exit();
}

use WPKirk\WPBones\Support\ServiceProvider;

/**
 * Class AdminRouteProvider
 *
 * This class is responsible for registering all the admin routes
 * by using the config/routes.php file.
 *
 * @package WPKirk\WPBones\Routing
 */

class AdminRouteProvider extends ServiceProvider
{
  // register
  public function register()
  {
    global $admin_page_hooks, $_registered_pages, $_parent_pages;

    $pages = include_once "{$this->plugin->getBasePath()}/config/routes.php";

    if (!empty($pages) && is_array($pages)) {
      foreach ($pages as $page_slug => $page) {
        $page_slug = plugin_basename($page_slug);
        $admin_page_hooks[$page_slug] = !isset($page['title']) ?: $page['title'];
        $hookName = get_plugin_page_hookname($page_slug, '');

        if (!empty($hookName)) {
          if ($hook = $this->plugin->getCallableHook($page['route'])) {
            add_action("load-toplevel_page_{$page_slug}", function () use ($page) {
              add_filter(
                'admin_title',
                function () use ($page) {
                  return $page['title'];
                },
                99,
                2
              );
            });

            add_action($hookName, $hook);

            $_registered_pages[$hookName] = true;
            $_parent_pages[$page_slug] = false;
          }
        }
      }
    }
  }
}
