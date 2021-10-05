<?php

namespace WPKirk\WPBones\Database;

use  WPKirk\WPBones\Database\Support\Collection;

/**
 * The Database Model provides a base class for all database models.
 *
 * @package WPKirk\WPBones\Database
 *
 * You should extend this class to create your own model.
 *
 *
 *
 */

abstract class Model
{

    /**
     * The database table name.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key column name.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The WordPress database object.
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * List of columns and their types.
     * That's is the desc of the table.
     *
     * @var array
     */
    private $columns = [];

    /**
     * The single row object.
     *
     * @var object
     */
    private $record;

    /**
     * The collection of rows.
     *
     * @var \WPKirk\WPBones\Database\Support\Collection
     */
    private $collection = [];



    public function __construct($record = null)
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        // init
        $this->getTableDescription();

        // init a single record
        if (!is_null($record)) {
            $this->setRecord($record);
        }
    }

    /**
     * Store the single record information.
     *
     * @param stdClass $record
     */
    private function setRecord($record)
    {
        $this->record = $record;
    }

    /*
    |--------------------------------------------------------------------------
    | Public methods
    |--------------------------------------------------------------------------
    |
    | Desite they are "private" they are the public methods available as by static or by instance.
    |
    */

    /**
     * Retrun a collection of all the records.
     *
     * @return Collection
     */
    private function all($columns = ['*'])
    {
        $column_string = is_array($columns) ? implode(',', $columns) : implode(',', func_get_args());

        $sql = "SELECT $column_string FROM `{$this->getTableName()}`";
        $results = $this->wpdb->get_results($sql);

        /**
         *     [0] => stdClass Object
         *      (
         *          [log_id] => 1
         *          [user_id] => 1
         *          [activity] => updated
         *          [object_id] => 0
         *          [object_type] => post
         *          [activity_date] => 2019-05-03 00:00:00
         *      )
         *      ...
         */

        //error_log(print_r($results, true));
         
        $collection = [];
        foreach ($results as $result) {
            $collection[] = new static($result);
        }

        $this->collection = new Collection($collection);

        //error_log(print_r($this->collection, true));

        return $this->collection;
    }



    /*
     |--------------------------------------------------------------------------
     | Magic methods
     |--------------------------------------------------------------------------
     |
     |
     |
     */

    /**
     * We will use this megic method to return the value of the column.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->record->{$name})) {
            return $this->record->{$name};
        }

        return null;
    }

    /**
     * We will use this magic mathod to call the private methods.
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this, $name)) {
            return call_user_func_array([$this, $name], $arguments);
        }
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
     * Return the JSON representation of the object.
     */
    public function __toString()
    {
        return json_encode($this->record);
    }
     

    /*
     |--------------------------------------------------------------------------
     | Internal Methods
     |--------------------------------------------------------------------------
     |
     |
     |
     */

    /**
     * Get the table description.
     *
     * @return void
     */
    protected function getTableDescription()
    {
        if (!empty($this->table)) {
            $desc = $this->wpdb->get_results("DESC `{$this->getWordPressTableName($this->table)}`");

            /**
             * [0] => stdClass Object
             *      (
             *          [Field] => ID
             *          [Type] => bigint(20) unsigned
             *          [Null] => NO
             *          [Key] => PRI
             *          [Default] =>
             *          [Extra] => auto_increment
             *      )
             *
             *  [1] => stdClass Object
             *      (
             *          [Field] => user_login
             *          [Type] => varchar(60)
             *          [Null] => NO
             *          [Key] => MUL
             *          [Default] =>
             *          [Extra] =>
             *      )
             */

            foreach ($desc as $column) {
                $this->columns[] = [
                    'name' => $column->Field,
                    'type' => $column->Type,
                    'null' => $column->Null,
                    'key' => $column->Key,
                    'default' => $column->Default,
                    'extra' => $column->Extra,
                ];
            }

            /**
             * [0] => Array
             *      (
             *          [name] => ID
             *          [type] => bigint(20) unsigned
             *          [null] => NO
             *          [key] => PRI
             *          [default] =>
             *          [extra] => auto_increment
             *      )
             *
             *  [1] => Array
             *      (
             *          [name] => user_login
             *          [type] => varchar(60)
             *          [null] => NO
             *          [key] => MUL
             *          [default] =>
             *          [extra] =>
             *      )
             */
        }
    }

    /**
     * Commodity method to get a WordPress table name.
     * Here we're going to add the prefix to the table name.
     *
     * @param  string $table_name
     * @return string
     */
    protected function getWordPressTableName($table)
    {
        return $this->wpdb->prefix . $table;
    }


    /*
     |--------------------------------------------------------------------------
     | Getters and Setters
     |--------------------------------------------------------------------------
     |
     | Special Getters and Setters for the model.
     |
     */

    protected function getTableName()
    {
        return $this->getWordPressTableName($this->table);
    }

    protected function setTableName($table)
    {
        $this->table = $table;
    }

    protected function getWpdb()
    {
        return $this->wpdb;
    }
}
