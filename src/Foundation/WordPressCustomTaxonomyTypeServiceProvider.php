<?php

namespace WPKirk\WPBones\Foundation;

use WPKirk\WPBones\Support\ServiceProvider;

if (! defined('ABSPATH')) {
    exit;
}

abstract class WordPressCustomTaxonomyTypeServiceProvider extends ServiceProvider
{

  /**
   * Taxonomy key, must not exceed 32 characters.
   *
   * @var string
   */
    protected $id = "wp_kirk_tax_id";

    protected $name   = "Ship";
    protected $plural = "Ships";

    /**
     * The custpm post type id.
     *
     * @var string
     */
    protected $objectType = "";


    /**
     * Name of the taxonomy shown in the menu. Usually plural. If not set, labels['name'] will be used.
     *
     * @var string
     */
    protected $label;

    /**
     * An array of labels for this taxonomy.
     * By default tag labels are used for non-hierarchical types and category labels for hierarchical ones.
     * You can see accepted values in {@link get_taxonomy_labels()}.
     *
     * @var array
     */
    protected $labels = [];

    /**
     * A short descriptive summary of what the taxonomy is for. Defaults to blank.
     *
     * @var string
     */
    protected $description = "";

    /**
     * If the taxonomy should be publicly queryable; //@TODO not implemented.
     * Defaults to true.
     *
     * @var bool
     */
    protected $public = true;

    /**
     * Whether the taxonomy is hierarchical (e.g. category). Defaults to false.
     *
     * @var bool
     */
    protected $hierarchical = false;

    /**
     * Whether to generate a default UI for managing this taxonomy in the admin.
     * If not set, the default is inherited from public.
     *
     * @var bool
     */
    protected $showUI = true;

    /**
     * Whether to show the taxonomy in the admin menu.
     * If true, the taxonomy is shown as a submenu of the object type menu.
     * If false, no menu is shown.
     * show_ui must be true.
     * If not set, the default is inherited from show_ui.
     *
     * @var bool
     */
    protected $showInMenu = true;

    /**
     * Makes this taxonomy available for selection in navigation menus.
     * If not set, the default is inherited from public.
     *
     * @var bool
     */
    protected $showInNavMenus = true;

    /**
     * Whether to list the taxonomy in the Tag Cloud Widget.
     * If not set, the default is inherited from show_ui.
     *
     * @var bool
     */
    protected $showTagcloud = true;

    /**
     * Whether to show the taxonomy in the quick/bulk edit panel.
     * It not set, the default is inherited from show_ui.
     *
     * @var bool
     */
    protected $showInQuickEdit = true;

    /**
     * Whether to display a column for the taxonomy on its post type listing screens.
     *
     * @var bool
     */
    protected $showAdminColumn = false;

    /**
     * Provide a callback function for the meta box display.
     * If not set, defaults to post_categories_meta_box for hierarchical taxonomies and post_tags_meta_box for
     * non-hierarchical. If false, no meta box is shown.
     *
     * @var null
     */
    protected $metaBoxCb = null;

    /**
     * Array of capabilities for this taxonomy. You can see accepted values in this function.
     *
     * @var array
     */
    protected $capabilities = [];

    /**
     * Triggers the handling of rewrites for this taxonomy. Defaults to true, using $taxonomy as slug.
     * To prevent rewrite, set to false. To specify rewrite rules, an array can be passed with any of these keys
     *
     * 'slug' => string Customize the permastruct slug. Defaults to $taxonomy key
     * 'with_front' => bool Should the permastruct be prepended with WP_Rewrite::$front. Defaults to true.
     * 'hierarchical' => bool Either hierarchical rewrite tag or not. Defaults to false.
     * 'ep_mask' => const Assign an endpoint mask.
     * If not specified, defaults to EP_NONE.
     *
     * @var array
     */
    protected $rewrite = [];

    /**
     * Customize the permastruct slug. Defaults to $taxonomy key
     *
     * @var string
     */
    protected $slug = "";

    /**
     * Should the permastruct be prepended with WP_Rewrite::$front. Defaults to true.
     *
     * @var bool
     */
    protected $withFront = true;

    /**
     * Either hierarchical rewrite tag or not. Defaults to false.
     *
     * @var bool
     */
    protected $rewriteHierarchical = false;

    /**
     * Assign an endpoint mask.
     *
     * @var int
     */
    protected $epMask = EP_NONE;


    /**
     * Sets the query_var key for this taxonomy. Defaults to $taxonomy key
     * If false, a taxonomy cannot be loaded at ?{query_var}={term_slug}
     * If specified as a string, the query ?{query_var_string}={term_slug} will be valid.
     *
     * @var string
     */
    protected $queryVar = "";

    /**
     * Works much like a hook, in that it will be called when the count is updated.
     * Defaults to _update_post_term_count() for taxonomies attached to post types, which then confirms that the objects
     * are published before counting them. Defaults to _update_generic_term_count() for taxonomies attached to other
     * object types, such as links.
     *
     * @var string
     */
    protected $updateCountCallback = '_update_post_term_count';

    /**
     * true if this taxonomy is a native or "built-in" taxonomy. THIS IS FOR INTERNAL USE ONLY!
     *
     * @var bool
     */
    //private $_builtin = true;


    public function register()
    {
        // you can override this method to set the properties
        $this->boot();

        // Register custom taxonomy
        register_taxonomy($this->id, $this->objectType, $this->args());
    }

    /**
     * You may override this method in order to register your own actions and filters.
     *
     */
    public function boot()
    {
        // You may override this method
    }


    /**
     * Return the default args.
     *
     * @return array
     */
    protected function args()
    {
        $defaults = [
      'labels'                => $this->labels(),
      'description'           => $this->description,
      'public'                => $this->public,
      'hierarchical'          => $this->hierarchical,
      'show_ui'               => $this->showUI,
      'show_in_menu'          => $this->showInMenu,
      'show_in_nav_menus'     => $this->showInNavMenus,
      'show_tagcloud'         => $this->showTagcloud,
      'show_in_quick_edit'    => $this->showInQuickEdit,
      'show_admin_column'     => $this->showAdminColumn,
      'meta_box_cb'           => $this->metaBoxCb,
      'capabilities'          => $this->capabilities,
      'rewrite'               => $this->rewrite(),
      'query_var'             => $this->queryVar,
      'update_count_callback' => $this->updateCountCallback,
    ];

        return $defaults;
    }

    /**
     * Return defaults rewrite.
     *
     * 'slug' => string Customize the permastruct slug. Defaults to $taxonomy key
     * 'with_front' => bool Should the permastruct be prepended with WP_Rewrite::$front. Defaults to true.
     * 'hierarchical' => bool Either hierarchical rewrite tag or not. Defaults to false.
     * 'ep_mask' => const Assign an endpoint mask.
     * If not specified, defaults to EP_NONE.
     *
     * @return array
     */
    protected function rewrite()
    {
        if (empty($this->rewrite)) {
            return [
        'slug'         => $this->id,
        'with_front'   => $this->withFront,
        'hierarchical' => $this->rewriteHierarchical,
        'ep_mask'      => $this->epMask
      ];
        }

        return $this->rewrite;
    }

    /**
     * Return defaults labels.
     *
     * @return array
     */
    protected function labels()
    {
        $defaults = [
      'name'                       => $this->plural,
      'singular_name'              => "{$this->name} category",
      'menu_name'                  => "{$this->name} categories",
      'name_admin_bar'             => "{$this->name} category",
      'search_items'               => "Search {$this->name} categories",
      'popular_items'              => "Popular {$this->name} categories",
      'all_items'                  => "All {$this->name} categories",
      'edit_item'                  => "Edit {$this->name} category",
      'view_item'                  => "View {$this->name} category",
      'update_item'                => "Updated {$this->name} category",
      'add_new_item'               => "Add new {$this->name} category",
      'new_item_name'              => "New {$this->name} category name",
      'separate_items_with_commas' => "Separate {$this->name} categories with comas",
      'add_or_remove_items'        => "Add or remove {$this->name} categories",
      'choose_from_most_used'      => "Choose from the most used {$this->name} categories",
    ];
        if (empty($this->labels)) {
            return $defaults;
        }

        return array_merge(
      $defaults,
      $this->labels
    );
    }
}
