<?php

namespace WPKirk\WPBones\Database;

use WPKirk\WPBones\Support\Str;

/**
 * The Database Model provides a base class for all database models.
 */
class DB
{
  /**
   * The query builder instance.
   *
   * @var \WPKirk\WPBones\Database\QueryBuilder
   */
  protected QueryBuilder $queryBuilder;

  /**
   * A key-value array of attributes of the attributes.
   *
   * @var array
   */
  public $attributes = [];

  /**
   * Will use the WordPress prefix of the database.
   *
   * @since 1.7.0
   * @var bool
   */
  protected $usePrefix = true;

  /**
   * Create a new DB model.
   *
   * @param string $table The table name.
   * @param string $primaryKey
   * @param bool $usePrefix Optional. @since 1.7.0 you can set this to false to not use the WordPress prefix. Default is true.
   */
  public function __construct($table, $primaryKey = 'id', $usePrefix = true)
  {
    $this->usePrefix = $usePrefix;
    $this->queryBuilder = new QueryBuilder($table, $primaryKey, $this->usePrefix);
    $this->queryBuilder->setParentModel($this);
  }

  /*
  |--------------------------------------------------------------------------
  | Public static and instance methods
  |--------------------------------------------------------------------------
  |
  |
  */

  /**
   * Instantiate a new DB model with the given table name.
   *
   * @param string $table The table name.
   * @param string $primaryKey
   * @param bool $usePrefix Optional. @since 1.7.0 you can set this to false to not use the WordPress prefix. Default is true.
   *
   * @see DB::tableWithoutPrefix()
   *
   * @return DB
   */
  public static function table(string $table, string $primaryKey = 'id', $usePrefix = true): DB
  {
    return new static($table, $primaryKey, $usePrefix);
  }

  /**
   * Instantiate a new DB model with the given table name without the WordPress prefix.
   *
   * @param string $table The table name.
   * @param string $primaryKey
   * @return DB
   */
  public static function tableWithoutPrefix(string $table, string $primaryKey = 'id'): DB
  {
    return new static($table, $primaryKey, false);
  }

  /**
   * Return a WordPress table name with the WordPress prefix.
   *
   * @param string $class
   * @param string $usePrefix Optional. @since 1.7.0 you can set this to false to not use the WordPress prefix.
   *                          Default is true.
   * @return string
   *
   * @example
   *          Model::tableName('User') returns 'wp_users'
   *          Model::tableName('WPMyTable') returns 'wp_w_p_my_table'
   *          Model::tableName('WP_MyTable') returns 'wp_w_p_my_table'
   *
   * @since 1.7.0
   * If you set the $usePrefix to false, it will not use the WordPress prefix.
   * @example
   *          Model::tableName('User', false) returns 'users'
   *          Model::tableName('WPMyTable', false) returns 'wp_my_table'
   *          Model::tableName('WP_MyTable', false) returns 'wp_my_table'
   *
   */
  public static function getTableName(string $class, $usePrefix = true): string
  {
    global $wpdb;

    $paths = explode('\\', $class);
    $only = array_pop($paths);
    $name = Str::snake(Str::studly($only));
    $prefix = $usePrefix ? $wpdb->prefix : '';

    return Str::startsWith($name, $prefix) ? $name : $prefix . $name;
  }

  /*
  |--------------------------------------------------------------------------
  | Getter and setter
  |--------------------------------------------------------------------------
  |
  |
  */

  /**
   * Set the primary key for the model.
   */
  public function setPrimaryKey($primaryKey)
  {
    $this->queryBuilder->setPrimaryKey($primaryKey);
  }

  /*
  |--------------------------------------------------------------------------
  | Magic methods
  |--------------------------------------------------------------------------
  |
  |
  */

  /**
   * Proxy method for the query builder.
   *
   * @param string $name      The method name.
   * @param array  $arguments The method arguments.
   */
  public function __call(string $name, array $arguments)
  {
    // we're going to call the same queryBuilder methods
    return call_user_func_array([$this->queryBuilder, $name], $arguments);
  }
}
