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

    /**
     * Return a single record by usign the primary key.
     */
    protected function get($id = null)
    {
        if (is_null($id)) {
            return $this->all();
        }

        $sql = "SELECT * " .
               "FROM `{$this->getTableName()}`" .
               $this->getWhereId($id);
        
        //logger()->info($sql);
                
        $result = $this->wpdb->get_row($sql, ARRAY_A);

        /**
         * Array
         *  (
         *      [log_id] => 3
         *      [user_id] => 3
         *      [activity] => running
         *      [object_id] => 0
         *      [object_type] => post
         *      [activity_date] => 0000-00-00 00:00:00
         *      [foo_bar] => 1
         *      [foo-bar] => 2
         *  )
         */

        return new static($result);
    }

    /**
     * Return the "where" clause for the primary key.
     *
     * @param int $id
     * @return string
     */
    private function getWhereId($id)
    {
        if (!empty($id)) {
            return " WHERE `{$this->primaryKey}` = " . $id;
        }

        return '';
    }
}
