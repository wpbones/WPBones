<?php

namespace WPKirk\WPBones\Database;

use WPKirk\WPBones\Database\QueryBuilder;

/**
 * The Database Model provides a base class for all database models.

 */

class DB
{
    protected $queryBuilder;

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
     * @return DB
     */
    public static function table($table, $primaryKey = 'id')
    {
        return new static($table, $primaryKey);
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

    public function __call($name, $arguments)
    {
        // we're goinf to call the same queryBuilder methods
        return call_user_func_array([$this->queryBuilder, $name], $arguments);
    }
}
