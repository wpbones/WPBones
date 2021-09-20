<?php

namespace WPKirk\WPBones\Routing;

if (! defined('ABSPATH')) {
    exit;
}

use WPKirk\WPBones\Support\ServiceProvider;

class AdminRouteProvider extends ServiceProvider
{

    // register
    public function register()
    {
        global $admin_page_hooks, $_registered_pages, $_parent_pages;

        $pages = include_once "{$this->plugin->getBasePath()}/config/routes.php";

        if (!empty($pages) && is_array($pages)) {
            foreach ($pages as $pageSlug => $page) {
                $pageSlug                    = plugin_basename($pageSlug);
                $admin_page_hooks[$pageSlug] = !isset($page['title']) ?: $page['title'];
                $hookName                    = get_plugin_page_hookname($pageSlug, '');

                if (!empty($hookName)) {
                    if ($hook = $this->plugin->getCallableHook($page['route'])) {
                        add_action($hookName, $hook);

                        $_registered_pages[$hookName] = true;
                        $_parent_pages[$pageSlug]     = false;
                    }
                }
            }
        }
    }
}
