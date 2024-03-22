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

  public function __construct($table, $primaryKey = 'id')
  {
    $this->queryBuilder = new QueryBuilder($table, $primaryKey);
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
   * @return DB
   */
  public static function table(string $table, string $primaryKey = 'id'): DB
  {
    return new static($table, $primaryKey);
  }

  /**
   * Return a WordPress table name with the WordPress prefix.
   *
   * @param string $class
   * @return string
   * @example Model::tableName('User') returns 'wp_users'
   *          Model::tableName('WPMyTable') returns 'wp_w_p_my_table'
   *          Model::tableName('WP_MyTable') returns 'wp_w_p_my_table'
   *
   */
  public static function getTableName(string $class): string
  {
    global $wpdb;

    $paths = explode('\\', $class);
    $only = array_pop($paths);
    $name = Str::snake(Str::studly($only));

    return Str::startsWith($name, $wpdb->prefix) ? $name : $wpdb->prefix . $name;
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
