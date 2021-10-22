<?php

namespace WPKirk\WPBones\Routing\Pages;

if (! defined('ABSPATH')) {
    exit;
}

use WPKirk\WPBones\Support\ServiceProvider;

/**
 * This provider is used to register all custom pages.
 *
 */
class PageProvider extends ServiceProvider
{
    // register
    public function register()
    {
        $this->initCustomRoutes();
    }

    /**
     * Return the first class defined in the given file.
     *
     * @param string $filename A PHP Filename file.
     *
     * @return string
     *
     * @suppress PHP0415
     */
    private function getFileClasses($filename)
    {
        $code = file_get_contents($filename);

        if (empty($code)) {
            return false;
        }

        $classes = [];
        $tokens = token_get_all($code);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS
            && $tokens[$i - 1][0] == T_WHITESPACE
            && $tokens[$i][0] == T_STRING
            ) {
                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }

        return array_pop($classes);
    }

    /**
     * Scan the 'pages' directory and load all the classes found.
     */
    private function initCustomRoutes()
    {
        global $admin_page_hooks, $_registered_pages, $_parent_pages;

        // the main path for the custom pages
        $folder = $this->plugin->getBasePath() . '/pages';

        $folder_exists = file_exists($folder);

        if ($folder_exists) {

            // register admin routes
            $admin_folder = $folder . '/admin';
            $admin_folder_exists = file_exists($admin_folder);

            error_log("admin_folder: " . $admin_folder);

            if ($admin_folder_exists) {
                foreach (glob("{$admin_folder}/*.php") as $filename) {
                    include_once $filename;

                    $class = $this->getFileClasses($filename);
                    $page = new $class($this->plugin);
                    
                    $pageSlug = plugin_basename(strtolower(str_replace('.php', '', basename($filename))));

                    $admin_page_hooks[$pageSlug] = $page->title();
                    $hookName = get_plugin_page_hookname($pageSlug, '');

                    add_action($hookName, function () use ($page) {
                        echo $page->render();
                    });

                    $_registered_pages[$hookName] = true;
                    $_parent_pages[$pageSlug] = false;
                }
            }
        }
    }
}
