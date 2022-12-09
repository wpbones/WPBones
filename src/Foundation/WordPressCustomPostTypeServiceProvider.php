<?php

namespace WPKirk\WPBones\Foundation;

use WPKirk\WPBones\Foundation\Http\Request;
use WPKirk\WPBones\Support\ServiceProvider;

if (!defined('ABSPATH')) {
  exit;
}

abstract class WordPressCustomPostTypeServiceProvider extends ServiceProvider
{

  /**
   * Post type key, must not exceed 20 characters.
   *
   * @var string
   */
  protected $id = '';

  /**
   * Name of the post type shown in the menu.
   *
   * @var string
   */
  protected $name = '';

  /**
   * Name of the post type shown in the menu as plural.
   *
   * @var string
   */
  protected $plural = '';

  /**
   * An array of labels for this post type.
   * If not set, post labels are inherited for non-hierarchical types and page labels for hierarchical ones.
   * You can see accepted values in {@link get_post_type_labels()}.
   *
   * @var array
   */
  protected $labels = [];

  /**
   * A short descriptive summary of what the post type is. Defaults to blank.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Whether a post type is intended for use publicly either via the admin interface or by front-end users.
   * Defaults to false.
   * While the default settings of exclude_from_search, publicly_queryable, show_ui, and show_in_nav_menus are
   * inherited from public, each does not rely on this relationship and controls a very specific intention.
   *
   * @var bool
   */
  protected $public = false;

  /**
   * Whether the post type is hierarchical (e.g. page). Defaults to false.
   *
   * @var bool
   */
  protected $hierarchical = false;

  /**
   * Whether to exclude posts with this post type from front end search results.
   * If not set, the opposite of public's current value is used.
   *
   * @var bool
   */
  protected $excludeFromSearch = true;

  /**
   * Whether queries can be performed on the front end for the post type as part of parse_request().
   *
   * ?post_type={post_type_key}
   * ?{post_type_key}={single_post_slug}
   * ?{post_type_query_var}={single_post_slug}
   *
   * If not set, the default is inherited from public.
   *
   * @var bool
   */
  protected $publiclyQueryable = false;

  /**
   * Whether to generate a default UI for managing this post type in the admin.
   * If not set, the default is inherited from public.
   *
   * @var bool
   */
  protected $showUI = true;

  /**
   * Where to show the post type in the admin menu.
   * If true, the post type is shown in its own top level menu.
   * If false, no menu is shown
   * If a string of an existing top level menu (eg. 'tools.php' or 'edit.php?post_type=page'), the post type will be
   * placed as a sub menu of that. show_ui must be true. If not set, the default is inherited from show_ui
   *
   * @var bool
   */
  protected $showInMenu = true;

  /**
   * Makes this post type available for selection in navigation menus.
   * If not set, the default is inherited from public.
   *
   * @var bool
   */
  protected $showInNavMenus = true;

  /**
   * Makes this post type available via the admin bar.
   * If not set, the default is inherited from show_in_menu
   *
   * @var bool
   */
  protected $showInAdminBar = true;

  /**
   * The position in the menu order the post type should appear.
   * show_in_menu must be true
   * Defaults to null, which places it at the bottom of its area.
   *
   * @var null
   */
  protected $menuPosition = null;

  /**
   * The url to the icon to be used for this menu. Defaults to use the posts icon.
   * Pass a base64-encoded SVG using a data URI, which will be colored to match the color scheme.
   * This should begin with 'data:image/svg+xml;base64,'.
   * Pass the name of a Dashicons helper class to use a font icon, e.g. 'dashicons-piechart'.
   * Pass 'none' to leave div.wp-menu-image empty so an icon can be added via CSS.
   *
   * @var string
   */
  protected $menuIcon = '';

  /**
   * The string to use to build the read, edit, and delete capabilities. Defaults to 'post'.
   * May be passed as an array to allow for alternative plurals when using this argument as a base to construct the
   * capabilities, e.g. array('story', 'stories').
   *
   * @var string
   */
  protected $capabilityType = 'post';

  /**
   * Array of capabilities for this post type.
   * By default, the capability_type is used as a base to construct capabilities.
   * You can see accepted values in {@link get_post_type_capabilities()}.
   *
   * @var array
   */
  protected $capabilities = [];

  /**
   * Whether to use the internal default meta capability handling. Defaults to false.
   *
   * @var bool
   */
  protected $mapMetaCap = false;

  /**
   * An alias for calling add_post_type_support() directly. Defaults to title and editor.
   * See {@link add_post_type_support()} for documentation.
   *
   * @var array
   */
  protected $supports = [];

  /**
   * Provide a callback function that sets up the meta boxes
   * for the edit form. Do remove_meta_box() and add_meta_box() calls in the callback.
   *
   * @var null
   */
  protected $registerMetaBoxCallback = null;

  /**
   * An array of taxonomy identifiers that will be registered for the post type.
   * Default is no taxonomies.
   * Taxonomies can be registered later with register_taxonomy() or register_taxonomy_for_object_type().
   *
   * @var array
   */
  protected $taxonomies = [];

  /**
   * True to enable post type archives. Default is false.
   * Will generate the proper rewrite rules if rewrite is enabled.
   *
   * @var bool
   */
  protected $hasArchive = false;

  /**
   * Triggers the handling of rewrites for this post type. Defaults to true, using $post_type as slug.
   * To prevent rewrite, set to false.
   * To specify rewrite rules, an array can be passed with any of these keys
   * 'slug' => string Customize the permastruct slug. Defaults to $post_type key
   * 'with_front' => bool Should the permastruct be prepended with WP_Rewrite::$front. Defaults to true.
   * 'feeds' => bool Should a feed permastruct be built for this post type. Inherits default from has_archive.
   * 'pages' => bool Should the permastruct provide for pagination. Defaults to true.
   * 'ep_mask' => const Assign an endpoint mask.
   * If not specified and permalink_epmask is set, inherits from permalink_epmask.
   * If not specified and permalink_epmask is not set, defaults to EP_PERMALINK
   *
   * @var array
   */
  protected $rewrite = [];

  /**
   * Customize the permastruct slug. Defaults to $post_type key
   *
   * @var string
   */
  protected $slug = "wp_kirk_slug";

  /**
   * Should the permastruct be prepended with WP_Rewrite::$front. Defaults to true.
   *
   * @var bool
   */
  protected $withFront = true;

  /**
   * Should a feed permastruct be built for this post type. Inherits default from has_archive.
   *
   * @var bool
   */
  protected $feeds = false;

  /**
   * Should the permastruct provide for pagination. Defaults to true.
   *
   * @var bool
   */
  protected $pages = true;

  /**
   * Assign an endpoint mask.
   * If not specified and permalink_epmask is set, inherits from permalink_epmask.
   * If not specified and permalink_epmask is not set, defaults to EP_PERMALINK
   *
   * @var int
   */
  protected $epMask = EP_PERMALINK;

  /**
   * Sets the query_var key for this post type. Defaults to $post_type key
   * If false, a post type cannot be loaded at ?{query_var}={post_slug}
   * If specified as a string, the query ?{query_var_string}={post_slug} will be valid.
   *
   * @var string
   */
  protected $queryVar = 'wp_kirk_vars';

  /**
   * Allows this post type to be exported. Defaults to true.
   *
   * @var bool
   */
  protected $canExport = true;

  /**
   * Whether to delete posts of this type when deleting a user.
   *
   * If true, posts of this type belonging to the user will be moved to trash when then user is deleted.
   * If false, posts of this type belonging to the user will *not* be trashed or deleted.
   * If not set (the default), posts are trashed if post_type_supports('author'). Otherwise posts are not trashed or
   * deleted.
   *
   * @var bool
   */
  protected $deleteWithUser = false;

  /**
   * true if this post type is a native or "built-in" post_type. THIS IS FOR INTERNAL USE ONLY!
   *
   * @var bool
   */
  //private $_builtin = false;

  /**
   * URL segments to use for edit link of this post type. THIS IS FOR INTERNAL USE ONLY!
   *
   * @var string
   */
  //private $_editLink = '';

  public function register()
  {
    // you can override this method to set the properties
    $this->boot();

    // Register custom post type
    register_post_type($this->id, $this->args());
    $this->initHooks();
  }

  /**
   * You may override this method in order to register your own actions and filters.
   *
   */
  public function boot()
  {
    // You may override this method
  }

  protected function args(): array
  {
    return [
      'labels'               => $this->labels(),
      'description'          => $this->description,
      'public'               => $this->public,
      'hierarchical'         => $this->hierarchical,
      'exclude_from_search'  => $this->excludeFromSearch,
      'publicly_queryable'   => $this->publiclyQueryable,
      'show_ui'              => $this->showUI,
      'show_in_menu'         => $this->showInMenu,
      'show_in_nav_menus'    => $this->showInNavMenus,
      'show_in_admin_bar'    => $this->showInAdminBar,
      'menu_position'        => $this->menuPosition,
      'menu_icon'            => $this->menuIcon,
      'capability_type'      => $this->capabilityType,
      'capabilities'         => $this->capabilities,
      'map_meta_cap'         => $this->mapMetaCap,
      'supports'             => $this->supports(),
      'register_meta_box_cb' => $this->registerMetaBoxCallback,
      'taxonomies'           => $this->taxonomies,
      'has_archive'          => $this->hasArchive,
      'rewrite'              => $this->rewrite(),
      'query_var'            => $this->queryVar,
      'can_export'           => $this->canExport,
      'delete_with_user'     => $this->deleteWithUser,
      //      '_builtin'             => $this->_builtin,
      //      '_edit_link'           => $this->_editLink,
    ];
  }

  /**
   * You can see accepted values in {@link get_post_type_labels()}.
   *
   * @return array
   */
  protected function labels(): array
  {
    $defaults = [
      'name'               => $this->plural,
      'singular_name'      => $this->name,
      'menu_name'          => $this->name,
      'name_admin_bar'     => $this->name,
      'add_new'            => "Add {$this->name}",
      'add_new_item'       => "Add New {$this->name}",
      'edit_item'          => "Edit {$this->name}",
      'new_item'           => "New {$this->name}",
      'view_item'          => "View {$this->name}",
      'search_items'       => "Search {$this->name}",
      'not_found'          => "No {$this->name} found",
      'not_found_in_trash' => "No {$this->name} found in trash",
      'all_items'          => $this->plural,
      'archive_title'      => $this->name,
      'parent_item_colon'  => '',
    ];
    if (empty($this->labels)) {
      return $defaults;
    }

    return array_merge(
      $defaults,
      $this->labels
    );
  }

  /**
   * See {@link add_post_type_support()} for documentation.
   *
   * @return array
   */
  protected function supports(): array
  {
    if (empty($this->supports)) {
      return [
        'title',
        'editor',
        'author',
        'thumbnail',
        'excerpt',
        'trackbacks',
        'custom-fields',
        'comments',
        'revisions',
        'post-formats',
      ];
    }

    return $this->supports;
  }

  /**
   * To specify rewrite rules, an array can be passed with any of these keys
   *
   *   'slug' => string Customize the permastruct slug. Defaults to $post_type key
   *   'with_front' => bool Should the permastruct be prepended with WP_Rewrite::$front. Defaults to true.
   *   'feeds' => bool Should a feed permastruct be built for this post type. Inherits default from has_archive.
   *   'pages' => bool Should the permastruct provide for pagination. Defaults to true.
   *   'ep_mask' => const Assign an endpoint mask.
   *
   * If not specified and permalink_epmask is set, inherits from permalink_epmask.
   * If not specified and permalink_epmask is not set, defaults to EP_PERMALINK
   *
   * @return array
   */
  protected function rewrite(): array
  {
    if (empty($this->rewrite)) {
      return [
        'slug'       => $this->slug,
        'with_front' => $this->withFront,
        'pages'      => $this->pages,
        'ep_mask'    => $this->epMask,
      ];
    }

    return $this->rewrite;
  }

  protected function initHooks()
  {
    // admin hooks
    if (is_admin()) {

      // Hook save post
      add_action('save_post_' . $this->id, [$this, 'save_post'], 10, 2);
    }
  }

  /**
   * Return TRUE if this custom post type is current view.
   *
   * @return bool
   */
  public function is(): bool
  {
    global $post_type, $typenow;

    return ($post_type === $this->id || $typenow === $this->id);
  }

  /**
   * This action is called when a post is saved or updated. Use the `save_post_{post_type}` hook
   *
   * @brief Save/update post
   * @note  You DO NOT override this method, use `update()` instead
   *
   * @param int|string $post_id Post ID
   * @param object     $post    Optional. Post object
   *
   * @return void
   */
  public function save_post($post_id, $post = null)
  {

    // Do not save...
    if ((defined('DOING_AUTOSAVE') && true === DOING_AUTOSAVE) ||
        (defined('DOING_AJAX') && true === DOING_AJAX) ||
        (defined('DOING_CRON') && true === DOING_CRON)
    ) {
      return;
    }

    // Get post type information
    $post_type        = get_post_type();
    $post_type_object = get_post_type_object($post_type);

    // Exit
    if (false == $post_type || is_null($post_type_object)) {
      return;
    }

    // This function only applies to the following post_types
    if (!in_array($post_type, [$this->id])) {
      return;
    }

    // Find correct capability from post_type arguments
    if (isset($post_type_object->cap->edit_posts)) {
      $capability = $post_type_object->cap->edit_posts;

      // Return if current user cannot edit this post
      if (!current_user_can($capability)) {
        return;
      }
    }

    // If all ok and post request then update()
    if (Request::isVerb('post')) {
      $this->update($post_id, $post);
    }
  }

  /**
   * Override this method to save/update your custom data.
   * This method is called by hook action save_post_{post_type}`
   *
   * @param int|string $post_id Post ID
   * @param object     $post    Optional. Post object
   *
   */
  public function update($post_id, $post)
  {
    // You can override this method to save your own data
  }
}
