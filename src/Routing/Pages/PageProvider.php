<?php

namespace Ondapresswp\WPBones\Routing\Pages;

if (!defined('ABSPATH')) {
  exit();
}

use Ondapresswp\WPBones\Support\ServiceProvider;

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
      if ($tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {
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
    $folder_pages = $this->plugin->basePath . '/pages';

    $folder_pages_exists = file_exists($folder_pages);

    if ($folder_pages_exists) {
      foreach (glob("{$folder_pages}/*.php") as $filename) {
        include_once $filename;

        $class = $this->getFileClasses($filename);
        $page = new $class($this->plugin);

        $page_slug = plugin_basename(strtolower(str_replace('.php', '', basename($filename))));

        $admin_page_hooks[$page_slug] = $page->title();
        $hookName = get_plugin_page_hookname($page_slug, '');

        add_action("load-toplevel_page_{$page_slug}", function () use ($page) {
          add_filter(
            'admin_title',
            function () use ($page) {
              return $page->title();
            },
            99,
            2
          );
        });

        add_action($hookName, function () use ($page) {
          echo $page->render();
        });

        $_registered_pages[$hookName] = true;
        $_parent_pages[$page_slug] = false;
      }
    }
  }
}
