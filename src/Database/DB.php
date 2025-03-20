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
     * @param string $prefixParam Optional. You can set a custom defined prefix. Default is empty.
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
    public static function getTableName(string $class, $usePrefix = true,$prefixParam=""): string
    {
        global $wpdb;

        $paths = explode('\\', $class);
        $only = array_pop($paths);
        $name = Str::snake(Str::studly($only));
        $WPMSTables = DB::getMultisiteTables();
        #ddjson($WPMSTables);
        if (is_multisite()) {
            $prefix = $usePrefix ? $wpdb->prefix : $prefixParam;
            $name = str_replace(str_replace( "_", "",$wpdb->base_prefix).get_current_blog_id()."_", "", $name);
            $prefix=$usePrefix?(in_array($name,$WPMSTables)?$wpdb->base_prefix:$wpdb->prefix):$prefixParam;
        } else {
            $prefix = $usePrefix ? $wpdb->prefix : $prefixParam;
        }
        return Str::startsWith($name, $prefix) ? $name : $prefix . $name;
    }

    /**
     * Return the site-wide tables
     */
    public static function getMultisiteTables(){
        global $wpdb;
        $prefix = is_multisite()? $wpdb->base_prefix : $wpdb->prefix;
        return[
            $prefix."blogs","blogs",
            $prefix."blog_versions","blog_versions",
            $prefix."registration_log","registration_log",
            $prefix."signups","signups",
            $prefix."site","site",
            $prefix."sitemeta","sitemeta",
            $prefix."users","users",
            $prefix."usermeta","usermeta",
        ];
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
