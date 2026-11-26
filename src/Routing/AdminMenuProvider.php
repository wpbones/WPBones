<?php

namespace WPKirk\WPBones\Routing;

if (!defined('ABSPATH')) {
  exit();
}

use WPKirk\WPBones\Support\ServiceProvider;
use WPKirk\WPBones\Support\Str;

/**
 * Class AdminMenuProvider
 *
 * This class is a service provider for the WP Bones framework.
 * Here we're going to read the menu configuration file and register the menus.
 *
 * @package WPKirk\WPBones\Routing
 */

class AdminMenuProvider extends ServiceProvider
{
  /**
   * The `icon` of config/menus.php, as add_menu_page() takes it.
   *
   * What WordPress reads by itself passes through: a Dashicons class (`dashicons-*`), a base64
   * SVG (`data:`), `none`, an absolute URL and, since WordPress 7.2, the name of a registered icon
   * (`core-admin/pin`). Anything else is a file in the plugin's public/images folder.
   *
   * An icon name is recognised only where WordPress supports it. A menu that must also run on
   * earlier versions gates it in config/menus.php:
   * `'icon' => function_exists('wp_is_valid_icon_name') ? 'core-admin/pin' : 'dashicons-admin-post'`.
   *
   * @param string $icon The `icon` value of config/menus.php.
   *
   * @return array{0: string, 1: bool} The icon, and whether WordPress draws it as an `<img>`.
   */
  protected function menuIcon(string $icon): array
  {
    if ($icon === '' || $icon === 'none' || strpos($icon, 'dashicons') === 0 || strpos($icon, 'data:') === 0) {
      return [$icon, false];
    }

    if (function_exists('wp_is_valid_icon_name') && wp_is_valid_icon_name($icon)) {
      return [$icon, false];
    }

    if (preg_match('#^(https?:)?//#i', $icon)) {
      return [$icon, true];
    }

    return [$this->plugin->images . '/' . $icon, true];
  }

  // register
  public function register()
  {
    $menus = include_once "{$this->plugin->basePath}/config/menus.php";

    if (!empty($menus) && is_array($menus)) {
      foreach ($menus as $topLevelSlug => $menu) {
        // sanitize array keys
        $menu['position'] = isset($menu['position']) ? $menu['position'] : null;
        // Since 3.0 a menu that says nothing is for administrators (it was `read` up to 2.x).
        $menu['capability'] = isset($menu['capability']) ? $menu['capability'] : 'manage_options';
        $menu['icon'] = isset($menu['icon']) ? $menu['icon'] : '';
        $page_title = isset($menu['page_title']) ? $menu['page_title'] : $menu['menu_title'];
        $menu['page_title'] = sanitize_title($page_title);

        [$icon, $hasImage] = $this->menuIcon((string) $menu['icon']);

        $firstMenu = true;

        if (substr($topLevelSlug, 0, 8) !== 'edit.php') {
          $suffix = add_menu_page(
            $menu['page_title'],
            $menu['menu_title'],
            $menu['capability'],
            $topLevelSlug,
            '',
            $icon,
            $menu['position']
          );

          if ($hasImage) {
            add_action('admin_head', function () use ($suffix) {
              echo '<style>li.' . $suffix . ' div.wp-menu-image img {padding:6px 0 !important;}</style>';
            });
          }
        } else {
          $firstMenu = false;
        }

        foreach ($menu['items'] as $key => $subMenu) {
          if (is_null($subMenu)) {
            continue;
          }

          // index 0
          if (empty($key)) {
            $key = '0';
          }

          // sanitize array keys
          $subMenu['capability'] = isset($subMenu['capability']) ? $subMenu['capability'] : $menu['capability'];
          $subMenu['page_title'] = isset($subMenu['page_title']) ? $subMenu['page_title'] : $subMenu['menu_title'];

          // key could be a number
          $key = str_replace('-', '_', sanitize_title($key));

          $array = explode('\\', __NAMESPACE__);
          $namespace = sanitize_title($array[0]);

          // submenu slug
          $submenuSlug = "{$namespace}_{$key}";

          if ($firstMenu) {
            $firstMenu = false;
            $submenuSlug = $topLevelSlug;
          }

          // get hook
          $hook = $this->plugin->getCallableHook($subMenu['route']);

          $subMenuHook = add_submenu_page(
            $topLevelSlug,
            $subMenu['page_title'],
            $subMenu['menu_title'],
            $subMenu['capability'],
            $submenuSlug,
            $hook
          );

          // Since 3.0 a request that is not a GET carries the plugin's nonce; a page that checks a
          // nonce of its own says 'csrf' => false.
          if ($subMenuHook && (($subMenu['route']['csrf'] ?? true) !== false)) {
            Csrf::guard($subMenuHook, $this->plugin->csrfAction());
          }

          if (isset($subMenu['route']['load'])) {
            [$controller, $method] = Str::parseCallback($subMenu['route']['load']);

            add_action("load-{$subMenuHook}", function () use ($controller, $method) {
              $className = "WPKirk\\Http\\Controllers\\{$controller}";
              $instance = new $className();

              return $instance->{$method}();
            });
          }

          if (isset($subMenu['route']['resource'])) {
            $controller = $subMenu['route']['resource'];

            add_action("load-{$subMenuHook}", function () use ($controller) {
              $className = "WPKirk\\Http\\Controllers\\{$controller}";
              $instance = new $className();
              if (method_exists($instance, 'load')) {
                return $instance->load();
              }
            });
          }
        }
      }
    }
  }
}
