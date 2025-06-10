<?php

namespace Ondapresswp\WPBones\Foundation;

use Ondapresswp\WPBones\Support\ServiceProvider;

if (!defined('ABSPATH')) {
 exit();
}

abstract class WordPressCustomTaxonomyTypeServiceProvider extends ServiceProvider
{
 /**
  * Taxonomy key. Must not exceed 32 characters and may only contain
  * lowercase alphanumeric characters, dashes, and underscores. See sanitize_key().
  *
  * It's the `$taxonomy` parameter used in
  * `register_taxonomy( $taxonomy, $object_type, $args = array() )`
  *
  * @var string
  */
 protected $id;

 /**
  * Object type or array of object types with which the taxonomy should be associated.
  *
  * It's the `$object_type` parameter used in
  * `register_taxonomy( $taxonomy, $object_type, $args = array() )`
  *
  * @var string
  */
 protected $objectType;

 /**
  * Name for one object of this taxonomy. Default 'Tag'/'Category'.
  *
  * @var string
  */
 protected $name;

 /**
  * General name for the taxonomy, usually plural.
  * The same as and overridden by `$tax->label`. Default 'Tags'/'Categories'.
  *
  * @var string
  */
 protected $plural;

 /**
  * Taxonomy labels object. The first default value is for non-hierarchical taxonomies
  * (like tags) and the second one is for hierarchical taxonomies (like categories).
  *
  *     @type string $name                       General name for the taxonomy, usually plural. The same
  *                                              as and overridden by `$tax->label`. Default 'Tags'/'Categories'.
  *     @type string $singular_name              Name for one object of this taxonomy. Default 'Tag'/'Category'.
  *     @type string $search_items               Default 'Search Tags'/'Search Categories'.
  *     @type string $popular_items              This label is only used for non-hierarchical taxonomies.
  *                                              Default 'Popular Tags'.
  *     @type string $all_items                  Default 'All Tags'/'All Categories'.
  *     @type string $parent_item                This label is only used for hierarchical taxonomies. Default
  *                                              'Parent Category'.
  *     @type string $parent_item_colon          The same as `parent_item`, but with colon `:` in the end.
  *     @type string $name_field_description     Description for the Name field on Edit Tags screen.
  *                                              Default 'The name is how it appears on your site'.
  *     @type string $slug_field_description     Description for the Slug field on Edit Tags screen.
  *                                              Default 'The &#8220;slug&#8221; is the URL-friendly version
  *                                              of the name. It is usually all lowercase and contains
  *                                              only letters, numbers, and hyphens'.
  *     @type string $parent_field_description   Description for the Parent field on Edit Tags screen.
  *                                              Default 'Assign a parent term to create a hierarchy.
  *                                              The term Jazz, for example, would be the parent
  *                                              of Bebop and Big Band'.
  *     @type string $desc_field_description     Description for the Description field on Edit Tags screen.
  *                                              Default 'The description is not prominent by default;
  *                                              however, some themes may show it'.
  *     @type string $edit_item                  Default 'Edit Tag'/'Edit Category'.
  *     @type string $view_item                  Default 'View Tag'/'View Category'.
  *     @type string $update_item                Default 'Update Tag'/'Update Category'.
  *     @type string $add_new_item               Default 'Add New Tag'/'Add New Category'.
  *     @type string $new_item_name              Default 'New Tag Name'/'New Category Name'.
  *     @type string $separate_items_with_commas This label is only used for non-hierarchical taxonomies. Default
  *                                              'Separate tags with commas', used in the meta box.
  *     @type string $add_or_remove_items        This label is only used for non-hierarchical taxonomies. Default
  *                                              'Add or remove tags', used in the meta box when JavaScript
  *                                              is disabled.
  *     @type string $choose_from_most_used      This label is only used on non-hierarchical taxonomies. Default
  *                                              'Choose from the most used tags', used in the meta box.
  *     @type string $not_found                  Default 'No tags found'/'No categories found', used in
  *                                              the meta box and taxonomy list table.
  *     @type string $no_terms                   Default 'No tags'/'No categories', used in the posts and media
  *                                              list tables.
  *     @type string $filter_by_item             This label is only used for hierarchical taxonomies. Default
  *                                              'Filter by category', used in the posts list table.
  *     @type string $items_list_navigation      Label for the table pagination hidden heading.
  *     @type string $items_list                 Label for the table hidden heading.
  *     @type string $most_used                  Title for the Most Used tab. Default 'Most Used'.
  *     @type string $back_to_items              Label displayed after a term has been updated.
  *     @type string $item_link                  Used in the block editor. Title for a navigation link block variation.
  *                                              Default 'Tag Link'/'Category Link'.
  *     @type string $item_link_description      Used in the block editor. Description for a navigation link block
  *                                              variation. Default 'A link to a tag'/'A link to a category'.
  *
  * @var string[]
  */
 protected $labels = [];

 /**
  * A short descriptive summary of what the taxonomy is for. Defaults to blank.
  *
  * @var string
  */
 protected $description = '';

 /**
  * If the taxonomy should be publicly queryable; //@TODO not implemented.
  * Defaults to true.
  *
  * @var bool
  */
 protected $public;

 /**
  * Whether the taxonomy is publicly queryable.
  * If not set, the default is inherited from `$public`.
  *
  * @var bool
  */
 protected $publiclyQueryable;

 /**
  * Whether the taxonomy is hierarchical (e.g. category).
  * Defaults to false.
  *
  * @var bool
  */
 protected $hierarchical;

 /**
  * Whether to generate a default UI for managing this taxonomy in the admin.
  * If not set, the default is inherited from public.
  *
  * @var bool
  */
 protected $showUI;

 /**
  * Whether to show the taxonomy in the admin menu.
  * If true, the taxonomy is shown as a submenu of the object type menu.
  * If false, no menu is shown.
  * show_ui must be true.
  * If not set, the default is inherited from show_ui.
  *
  * @var bool
  */
 protected $showInMenu;

 /**
  * Makes this taxonomy available for selection in navigation menus.
  * If not set, the default is inherited from public.
  *
  * @var bool
  */
 protected $showInNavMenus;

 /**
  * Whether to include the taxonomy in the REST API.
  * Set this to true for the taxonomy to be available in the block editor.
  *
  * @var bool
  */
 protected $showInRest;

 /**
  * Whether to list the taxonomy in the Tag Cloud Widget.
  * If not set, the default is inherited from show_ui.
  *
  * @var bool
  */
 protected $showTagcloud;

 /**
  * To change the base url of REST API route. Default is $taxonomy.
  *
  * @var string
  */
 protected $restBase;

 /**
  * To change the namespace of REST API route. Default is wp/v2.
  *
  * @var string
  */
 protected $restNamespace;

 /**
  * REST API Controller class name. Default is 'WP_REST_Terms_Controller'.
  *
  * @var string
  */
 protected $restControllerClass;

 /**
  * Whether to list the taxonomy in the Tag Cloud Widget controls.
  * If not set, the default is inherited from `$show_ui` (default true).
  *
  * @var bool
  */
 protected $showTagCloud;

 /**
  * Whether to show the taxonomy in the quick/bulk edit panel.
  * It not set, the default is inherited from show_ui.
  *
  * @var bool
  */
 protected $showInQuickEdit;

 /**
  * Whether to display a column for the taxonomy on its post type listing screens.
  *
  * @var bool
  */
 protected $showAdminColumn;

 /**
  * Provide a callback function for the meta box display.
  * If not set, defaults to post_categories_meta_box for hierarchical taxonomies and post_tags_meta_box for
  * non-hierarchical. If false, no meta box is shown.
  *
  * @var null
  */
 protected $metaBoxCb;

 /**
  * Callback function for sanitizing taxonomy data saved from a meta box.
  * If no callback is defined, an appropriate one is determined
  * based on the value of `$metaBoxCb`.
  *
  * @var null
  */
 protected $metaBoxSanitizeCb;

 /**
  * Array of capabilities for this taxonomy.
  *
  * @type string $manage_terms Default 'manage_categories'.
  * @type string $edit_terms   Default 'manage_categories'.
  * @type string $delete_terms Default 'manage_categories'.
  * @type string $assign_terms Default 'edit_posts'.
  *
  * @var array
  */
 protected $capabilities = [];

 /**
  * Array of the columns that should be added to the post type table.
  *
  * @since 1.9.0
  */
 private $columns = [];

 /**
  * Triggers the handling of rewrites for this taxonomy. Defaults to true, using $taxonomy as slug.
  * To prevent rewrite, set to false.
  * To specify rewrite rules, an array can be passed with any of these keys:
  *
  * @type string $slug         Customize the permastruct slug. Default `$taxonomy` key.
  * @type bool   $with_front   Should the permastruct be prepended with WP_Rewrite::$front. Default true.
  * @type bool   $hierarchical Either hierarchical rewrite tag or not. Default false.
  * @type int    $ep_mask      Assign an endpoint mask. Default `EP_NONE`.
  *
  * @var array
  */
 protected $rewrite = [];

 /**
  * Customize the permastruct slug. Defaults to $taxonomy key
  *
  * @var string
  */
 protected $slug;

 /**
  * Should the permastruct be prepended with WP_Rewrite::$front. Defaults to true.
  *
  * @var bool
  */
 protected $withFront;

 /**
  * Either hierarchical rewrite tag or not. Defaults to false.
  *
  * @var bool
  */
 protected $rewriteHierarchical;

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
 protected $queryVar;

 /**
  * Works much like a hook, in that it will be called when the count is updated.
  * Defaults to _update_post_term_count() for taxonomies attached to post types, which then confirms that the objects
  * are published before counting them.
  * Defaults to _update_generic_term_count() for taxonomies attached to other
  * object types, such as links.
  *
  * @var string
  */
 protected $updateCountCallback;

 /**
  * Default term to be used for the taxonomy.
  *
  * @type string $name         Name of default term.
  * @type string $slug         Slug for default term. Default empty.
  * @type string $description  Description for default term. Default empty.
  *
  * @var string|array
  */
 protected $defaultTerm;

 /**
  * Whether terms in this taxonomy should be sorted in the order they are
  * provided to `wp_set_object_terms()`. Default null which equates to false.
  *
  * @var bool
  */
 protected $sort;

 /**
  * Array of arguments to automatically use inside `wp_get_object_terms()`
  * for this taxonomy.
  *
  * @var array
  */
 protected $args = [];

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
  register_taxonomy($this->id, $this->objectType, $this->optionalArgs());
  if (isset($this->optionalArgs()["show_in_menu"]) && is_string($this->optionalArgs()["show_in_menu"])) {
   $parent = $this->optionalArgs()["show_in_menu"];
   if (! isset($this->plugin->menuRelations[$parent])) {
    $this->plugin->menuRelations[$parent] = [];
   }
   $this->plugin->menuRelations[$parent][] = ["url" => "edit-tags.php?taxonomy=" . $this->id, "name" => $this->optionalArgs()["labels"]["name"], "capabilities" => $this->optionalArgs()["capabilities"]];
  }
  $this->initHooks();
 }

 /**
  * You may override this method in order to register your own actions and filters.
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
 protected function optionalArgs(): array
 {

  $mapProperties = [
   'labels'                => 'labels',
   'description'           => 'description',
   'public'                => 'public',
   'publicly_queryable'    => 'publiclyQueryable',
   'hierarchical'          => 'hierarchical',
   'show_ui'               => 'showUI',
   'show_in_menu'          => 'showInMenu',
   'show_in_nav_menus'     => 'showInNavMenus',
   'show_in_rest'          => 'showInRest',
   'rest_base'             => 'restBase',
   'rest_namespace'        => 'restNamespace',
   'rest_controller_class' => 'restControllerClass',
   'show_tagcloud'         => 'showTagcloud',
   'show_in_quick_edit'    => 'showInQuickEdit',
   'show_admin_column'     => 'showAdminColumn',
   'meta_box_cb'           => 'metaBoxCb',
   'meta_box_sanitize_cb'  => 'metaBoxSanitizeCb',
   'capabilities'          => 'capabilities',
   'rewrite'               => 'rewrite',
   'query_var'             => 'queryVar',
   'update_count_callback' => 'updateCountCallback',
   'default_term'          => 'defaultTerm',
   'sort'                  => 'sort',
   'args'                  => 'args',
  ];

  return $this->mapPropertiesToArray($mapProperties);
 }

 /**
  * Return defaults labels.
  *
  *     @type string $name                       General name for the taxonomy, usually plural. The same
  *                                              as and overridden by `$tax->label`. Default 'Tags'/'Categories'.
  *     @type string $singular_name              Name for one object of this taxonomy. Default 'Tag'/'Category'.
  *     @type string $search_items               Default 'Search Tags'/'Search Categories'.
  *     @type string $popular_items              This label is only used for non-hierarchical taxonomies.
  *                                              Default 'Popular Tags'.
  *     @type string $all_items                  Default 'All Tags'/'All Categories'.
  *     @type string $parent_item                This label is only used for hierarchical taxonomies. Default
  *                                              'Parent Category'.
  *     @type string $parent_item_colon          The same as `parent_item`, but with colon `:` in the end.
  *     @type string $name_field_description     Description for the Name field on Edit Tags screen.
  *                                              Default 'The name is how it appears on your site'.
  *     @type string $slug_field_description     Description for the Slug field on Edit Tags screen.
  *                                              Default 'The &#8220;slug&#8221; is the URL-friendly version
  *                                              of the name. It is usually all lowercase and contains
  *                                              only letters, numbers, and hyphens'.
  *     @type string $parent_field_description   Description for the Parent field on Edit Tags screen.
  *                                              Default 'Assign a parent term to create a hierarchy.
  *                                              The term Jazz, for example, would be the parent
  *                                              of Bebop and Big Band'.
  *     @type string $desc_field_description     Description for the Description field on Edit Tags screen.
  *                                              Default 'The description is not prominent by default;
  *                                              however, some themes may show it'.
  *     @type string $edit_item                  Default 'Edit Tag'/'Edit Category'.
  *     @type string $view_item                  Default 'View Tag'/'View Category'.
  *     @type string $update_item                Default 'Update Tag'/'Update Category'.
  *     @type string $add_new_item               Default 'Add New Tag'/'Add New Category'.
  *     @type string $new_item_name              Default 'New Tag Name'/'New Category Name'.
  *     @type string $separate_items_with_commas This label is only used for non-hierarchical taxonomies. Default
  *                                              'Separate tags with commas', used in the meta box.
  *     @type string $add_or_remove_items        This label is only used for non-hierarchical taxonomies. Default
  *                                              'Add or remove tags', used in the meta box when JavaScript
  *                                              is disabled.
  *     @type string $choose_from_most_used      This label is only used on non-hierarchical taxonomies. Default
  *                                              'Choose from the most used tags', used in the meta box.
  *     @type string $not_found                  Default 'No tags found'/'No categories found', used in
  *                                              the meta box and taxonomy list table.
  *     @type string $no_terms                   Default 'No tags'/'No categories', used in the posts and media
  *                                              list tables.
  *     @type string $filter_by_item             This label is only used for hierarchical taxonomies. Default
  *                                              'Filter by category', used in the posts list table.
  *     @type string $items_list_navigation      Label for the table pagination hidden heading.
  *     @type string $items_list                 Label for the table hidden heading.
  *     @type string $most_used                  Title for the Most Used tab. Default 'Most Used'.
  *     @type string $back_to_items              Label displayed after a term has been updated.
  *     @type string $item_link                  Used in the block editor. Title for a navigation link block variation.
  *                                              Default 'Tag Link'/'Category Link'.
  *     @type string $item_link_description      Used in the block editor. Description for a navigation link block
  *                                              variation. Default 'A link to a tag'/'A link to a category'.
  *
  * @return array
  */
 protected function labels(): array
 {
  $defaults = [
   'name'                       => $this->plural,
   'singular_name'              => "{$this->name} category",
   'search_items'               => "Search {$this->name} categories",
   'popular_items'              => "Popular {$this->name} categories",
   'all_items'                  => "All {$this->name} categories",
   'parent_item'                => "Parent {$this->name} category",
   'parent_item_colon'          => "Parent {$this->name} category:",
   'slug_field_description'     => "The &#8220;slug&#8221;",
   'parent_field_description'   => "Assign a parent {$this->name} category to create a hierarchy.",
   'desc_field_description'     => "The description is not prominent by default",
   'menu_name'                  => "{$this->name} categories",
   'edit_item'                  => "Edit {$this->name} category",
   'view_item'                  => "View {$this->name} category",
   'update_item'                => "Updated {$this->name} category",
   'add_new_item'               => "Add new {$this->name} category",
   'new_item_name'              => "New {$this->name} category name",
   'separate_items_with_commas' => "Separate {$this->name} categories with comas",
   'add_or_remove_items'        => "Add or remove {$this->name} categories",
   'choose_from_most_used'      => "Choose from the most used {$this->name} categories",
   'not_found'                  => "No {$this->name} categories found",
   'no_terms'                   => "No {$this->name} categories",
   'filter_by_item'             => "Filter by {$this->name} category",
   'items_list_navigation'      => "{$this->name} categories list navigation",
   'items_list'                 => "{$this->name} categories list",
   'most_used'                  => "Most used {$this->name} categories",
   'back_to_items'              => "Back to {$this->name} categories",
   'item_link'                  => "{$this->name} category link",
   'item_link_description'      => "A link to a {$this->name} category",
  ];

  if (empty($this->labels)) {
   return $defaults;
  }

  return array_merge($defaults, $this->labels);
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
 protected function rewrite(): array
 {

  if (! empty($this->rewrite)) {
   return $this->rewrite;
  }

  $mapProperties = [
   'slug'         => 'id',
   'with_front'   => 'withFront',
   'hierarchical' => 'rewriteHierarchical',
   'ep_mask'      => 'epMask',
  ];

  return $this->mapPropertiesToArray($mapProperties);
 }
 /**
  * Initialize hooks
  */
 protected function initHooks()
 {
  add_action('admin_init', [$this, 'addTaxonomyColumns']);
 }
 public function addTaxonomyColumns()
 {

  add_filter('manage_' . $this->id . '_custom_column', [$this, 'columnContent'], 15, 3);
  add_filter('manage_edit-' . $this->id . '_columns', [$this, '_manage_taxonomy_columns']);
 }
 /**
  * Manage columns
  *
  * @param array $columns
  *
  * @since 1.9.0
  * @return array
  */

 public function _manage_taxonomy_columns($columns)
 {
  $newColumns = $this->registerColumns();
  $cb         = [];
  $cnt        = [];
  if (isset($columns["cb"])) {
   $cb = ["cb" => $columns["cb"]];
   unset($columns["cb"]);
  }
  if (isset($columns["posts"])) {
   $cnt = ["posts" => $columns["posts"]];
   unset($columns["posts"]);
  }
  return array_merge($cb, $columns, $newColumns, $cnt);
 }

 /**
  * Return the column content
  *
  * @param string $string
  * @param string $column_name
  * @param int $term_id
  *
  * @since 1.9.0
  * @return string
  */
 public function columnContent($string, $column_name, $term_id)
 {
  // You may override this method
  return $string;
 }

}
