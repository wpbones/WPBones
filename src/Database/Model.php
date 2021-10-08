<?php

namespace WPKirk\WPBones\Database;

use WPKirk\WPBones\Database\DB;

/**
 * The Database Model provides a base class for all database models.
 *
 * @package WPKirk\WPBones\Database
 *
 * @method static all()
 *
 */

abstract class Model extends DB
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct($this->table, $this->primaryKey);
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
