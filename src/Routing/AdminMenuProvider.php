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
    // this will help us to detect if we move some auto-assigned menu from CTT
    /**
     * Helper to know if the auto-assigned menu is in the current URL Path
     * @var bool
     */
    private bool $fromLink = false;
    /**
     * The Currently Top Level Slug
     * @var string
     */
    private string $topLevelSlug = "";
    // register
    public function register()
    {
        $menus        = include_once "{$this->plugin->basePath}/config/menus.php";
        if (! empty($menus) && is_array($menus)) {

            foreach ($menus as $topLevelSlug => $menu) {
                $this->topLevelSlug = $topLevelSlug;
                // sanitize array keys
                $menu['position']   = isset($menu['position']) ? $menu['position'] : null;
                $menu['capability'] = isset($menu['capability']) ? $menu['capability'] : 'read';
                $menu['icon']       = isset($menu['icon']) ? $menu['icon'] : '';
                $page_title         = isset($menu['page_title']) ? $menu['page_title'] : $menu['menu_title'];
                $menu['page_title'] = sanitize_title($page_title);

                // icon
                $icon     = $menu['icon'];
                $hasImage = false;

                if (isset($menu['icon']) && ! empty($menu['icon']) && 'dashicons' != substr($menu['icon'], 0, 9) && 'data:' != substr($menu['icon'], 0, 5)) {
                    $icon     = $this->plugin->images . '/' . $menu['icon'];
                    $hasImage = true;
                }

                $firstMenu = true;

                if (substr($this->topLevelSlug, 0, 8) !== 'edit.php') {
                    $suffix = add_menu_page(
                        __($menu['page_title'], WPBONES_TEXTDOMAIN),
                        __($menu['menu_title'], WPBONES_TEXTDOMAIN),
                        $menu['capability'],
                        $this->topLevelSlug,
                        isset($menu["callback"]) ? $menu["callback"] : '',
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

                if (! empty($menu['items'])) {
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

                        $array     = explode('\\', __NAMESPACE__);
                        $namespace = sanitize_title($array[0]);

                        // submenu slug
                        $submenuSlug = "{$namespace}_{$key}";

                        if ($firstMenu) {
                            $firstMenu   = false;
                            $submenuSlug = $this->topLevelSlug;
                        }

                        // get hook
                        if (isset($subMenu["route"])) {
                            $hook = $this->plugin->getCallableHook($subMenu['route']);
                        } elseif (isset($subMenu["url"])) {
                            //if the submenu is an url, we remove the hook, and use the submenu url as target url
                            $submenuSlug = $subMenu["url"];
                            $hook        = "";

                        }

                        $subMenuHook = add_submenu_page(
                            $this->topLevelSlug,
                            __($subMenu['page_title'], WPBONES_TEXTDOMAIN),
                            __($subMenu['menu_title'], WPBONES_TEXTDOMAIN),
                            $subMenu['capability'],
                            $submenuSlug,
                            $hook
                        );

                        if (isset($subMenu['route']['load'])) {
                            [$controller, $method] = Str::parseCallback($subMenu['route']['load']);

                            add_action("load-{$subMenuHook}", function () use ($controller, $method) {
                                $className = "WPKirk\\Http\\Controllers\\{$controller}";
                                $instance  = new $className();

                                return $instance->{$method}();
                            });
                        }

                        if (isset($subMenu['route']['resource'])) {
                            $controller = $subMenu['route']['resource'];

                            add_action("load-{$subMenuHook}", function () use ($controller) {
                                $className = "WPKirk\\Http\\Controllers\\{$controller}";
                                $instance  = new $className();
                                if (method_exists($instance, 'load')) {
                                    return $instance->load();
                                }
                            });
                        }
                    }
                }
                // if we had a parent menu with the same name as TopLevelSlug we will add the admin sub menu taken from CTT
                if (isset($this->plugin->menuRelations[$this->topLevelSlug]) && ! empty($this->plugin->menuRelations[$this->topLevelSlug])) {
                    foreach ($this->plugin->menuRelations[$this->topLevelSlug] as $menuInternal) {
                        if (str_contains($_SERVER['REQUEST_URI'], $menuInternal["url"])) {
                            $this->fromLink = true;
                        }
                        add_submenu_page(
                            $this->topLevelSlug,
                            __($menuInternal['name'], WPBONES_TEXTDOMAIN),
                            __($menuInternal['name'], WPBONES_TEXTDOMAIN),
                            "read",
                            $menuInternal["url"],
                            ""
                        );
                        remove_submenu_page("edit.php", $menuInternal["url"]);
                    }

                    add_filter("parent_file", [$this, "renameParentFile"], 10, 1);

                }
                //if our callback is # we remove the submenu page, with this we avoid the duplicate menu and submenu with the same name
                if (isset($menu["callback"]) && $menu["callback"] == "#") {
                    remove_submenu_page($this->topLevelSlug, $this->topLevelSlug);
                }
            }
        }
    }
    public function renameParentFile($parent_file)
    {
        if ($this->fromLink) {
            return $this->topLevelSlug;
        }
        return $parent_file;
    }

}
