<?php

namespace WPKirk\WPBones\Database\Support;

use WPKirk\WPBones\Database\QueryBuilder;

if (!defined('ABSPATH')) {
    exit;
}

class Model
{
    /**
     * The queryBuilder instance.
     *
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * A key-value array of attributes of the record.
     *
     * @var array
     */
    protected $record = [];

    public function __construct($record, $queryBuilder)
    {
        $this->record = $record;
        $this->queryBuilder = $queryBuilder;
    }

    /*
    |--------------------------------------------------------------------------
    | Public static and instance methods
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Delete the record from the database.
     */
    public function delete()
    {
        return $this->newQueryBuilder()
        ->where($this->getPrimaryKey(), $this->getPrimaryKeyValue())->delete();
    }

    /**
     * Update the record in the database.
     */
    public function update($record = null)
    {
        $record = $record ?: $this->record;

        return $this->newQueryBuilder()
        ->where($this->getPrimaryKey(), $this->getPrimaryKeyValue())->update($record);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal methods
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Return the primary key name.
     * @return string
     */
    protected function getPrimaryKey()
    {
        return $this->queryBuilder->getPrimaryKey();
    }

    /**
     * Return the primary key value.
     * @return string
     */
    protected function getPrimaryKeyValue()
    {
        return $this->record[$this->queryBuilder->getPrimaryKey()];
    }

    /**
     * Create a new queryBuilder instance.
     *
     * @return QueryBuilder
     */
    protected function newQueryBuilder()
    {
        return new QueryBuilder($this->queryBuilder->getTable(), $this->queryBuilder->getPrimaryKey());
    }


    /*
    |--------------------------------------------------------------------------
    | Magic methods
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Return the value of the given attribute.
     */
    public function __get($name)
    {
        if (isset($this->record[$name])) {
            return $this->record[$name];
        }
    }

    /**
     * Set the value of the given attribute.
     */
    public function __set($name, $value)
    {
        $this->record[$name] = $value;
    }

    /**
     * Return the JSON representation of the record.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->record);
    }

    /**
     * Return a JSON pretty version of the record.
     *
     * @return string
     */
    public function dump()
    {
        return json_encode($this->record, JSON_PRETTY_PRINT);
    }
}
