<?php

namespace WPKirk\WPBones\Database;

use WPKirk\WPBones\Database\DB;

/**
 * The Database Model provides a base class for all database models.
 *
 * @package WPKirk\WPBones\Database
 *
 *
 */

abstract class Model extends DB
{

    /**
     * The primary key column name.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    public function __construct($record = null)
    {
        parent::__construct($record, $this->table);
    }

    /**
     * We will this magic method to handle all statuc/instance methods.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return (new static)->$name(...$arguments);
    }
}
