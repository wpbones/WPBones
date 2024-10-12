<?php

namespace WPKirk\WPBones\Database;

use InvalidArgumentException;
use WPKirk\WPBones\Database\Support\Collection;
use WPKirk\WPBones\Database\Support\Model;

/**
 * Class QueryBuilder
 */
class QueryBuilder
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
  protected $wpdb;

  /**
   * List of columns and their types.
   * That is the desc of the table.
   *
   * @var array
   */
  private $columns = [];

  /**
   * The select columns.
   */
  private $select_columns = [];

  /**
   * The orderings for the query.
   *
   * @var array
   */
  private $orders = [];

  /**
   * The maximum number of records to return.
   *
   * @var int
   */
  private $limit = '';

  /**
   * The number of records to skip.
   *
   * @var int
   */
  private $offset;

  /**
   * The where conditions for the query.
   *
   * @var array
   */
  private $wheres = [];

  /**
   * All the available clause operators.
   *
   * @var array
   */
  private $operators = [
    '=',
    '<',
    '>',
    '<=',
    '>=',
    '<>',
    '!=',
    '<=>',
    'like',
    'like binary',
    'not like',
    'ilike',
    '&',
    '|',
    '^',
    '<<',
    '>>',
    'rlike',
    'not rlike',
    'regexp',
    'not regexp',
    '~',
    '~*',
    '!~',
    '!~*',
    'similar to',
    'not similar to',
    'not ilike',
    '~~*',
    '!~~*',
  ];

  /**
   * The collection of rows.
   *
   * @var \WPKirk\WPBones\Database\Support\Collection
   */
  private $collection = [];

  /**
   * The parent model instance used for extends the Model class.
   */
  private $parentModel;

  /**
   * The constructor.
   *
   * @param string $table The table name.
   * @param string $primaryKey The primary key column name.
   * @param bool $usePrefix Optional. @since 1.7.0 - Will use the WordPress prefix of the database. Default is true.
   * @return void         The QueryBuilder instance.
   */
  public function __construct($table, $primaryKey = 'id', $usePrefix = true)
  {
    global $wpdb;

    $this->wpdb = $wpdb;
    $this->table = DB::getTableName($table, $usePrefix);
    $this->primaryKey = $primaryKey;

    // init
    $this->getTableDescription();
  }

  /*
  |--------------------------------------------------------------------------
  | Magic methods
  |--------------------------------------------------------------------------
  |
  |
  */

  /*
  |--------------------------------------------------------------------------
  | Public methods
  |--------------------------------------------------------------------------
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
      $desc = $this->wpdb->get_results("DESC `{$this->table}`");

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
   * Return a collection of records that match the given where conditions.
   *
   * @return Collection
   */
  public function get()
  {
    return $this->all();
  }

  /**
   * Return a collection of all the records.
   *
   * @return Collection
   */
  public function all($columns = ['*'])
  {
    $columns = $this->select_columns ?: $columns;
    sort($columns);
    $column_string = is_array($columns) ? implode(',', $columns) : implode(',', func_get_args());

    $sql =
      "SELECT $column_string " .
      "FROM `{$this->table}`" .
      $this->getWhere() .
      $this->getOrderBy() .
      $this->getLimit() .
      $this->getOffset();

    $results = $this->getSQLResults($sql);

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

    $collection = [];
    foreach ($results as $result) {
      $collection[] = new Model($result, $this);
    }

    $this->collection = new Collection($collection);

    //error_log(print_r($this->collection, true));

    // reset the where conditions
    //$this->wheres = [];

    return $this->collection;
  }

  /**
   * Return the "where" part of the query.
   *
   * @return string
   */
  private function getWhere()
  {
    $where = ' WHERE 1 ';
    if (!empty($this->wheres)) {
      foreach ($this->wheres as $where_item) {
        $boolean = strtoupper($where_item['boolean']);
        $column = $where_item['column'];
        $operator = $this->getWhereOperator($where_item['operator']);
        $value = $this->getWhereValue($where_item['value'], $operator);

        $where .= $boolean . ' ' . $column . ' ' . $operator . ' ' . $value . ' ';
      }
    }

    return $where;
  }

  /**
   * Return the operator for the where clause.
   *
   * @param string $operator
   */
  private function getWhereOperator($operator)
  {
    return $operator ?? '=';
  }

  /**
   * Return the right format for the where value.
   *
   * @param string $value    The value to format.
   * @param string $operator Type of operator.
   */
  private function getWhereValue($value, $operator)
  {
    if (is_array($value) && in_array($operator, ['IN', 'NOT IN'])) {
      return '(' . implode(',', $value) . ')';
    }

    if (is_array($value) && in_array($operator, ['BETWEEN'])) {
      return implode(' AND ', $this->getFormatValue($value));
    }

    return $this->getFormatValue($value);
  }

  /**
   * Return the right format for the value.
   *
   * @param mixed $value The value to format.
   * @return mixed
   */
  private function getFormatValue($value)
  {
    if (is_array($value)) {
      return array_map(function ($value) {
        return $this->getFormatValue($value);
      }, $value);
    }

    return is_numeric($value) ? $value : "'" . $value . "'";
  }

  /**
   * Return the "order by" clause.
   *
   * @return string
   */
  private function getOrderBy()
  {
    if (!empty($this->orders)) {
      $orders = [];
      foreach ($this->orders as $order) {
        $orders[] = $order[0] . ' ' . $order[1];
      }

      return ' ORDER BY ' . implode(',', $orders);
    }

    return '';
  }

  /**
   * Return the "limit" clause.
   *
   * @return string
   */
  private function getLimit()
  {
    if (!empty($this->limit)) {
      return ' LIMIT ' . $this->limit;
    }

    return '';
  }

  /**
   * Return the "offset" clause.
   *
   * @return string
   */
  private function getOffset()
  {
    if (!empty($this->offset)) {
      $offset = max(0, (int) $this->offset);
      if ($offset > 0) {
        if (empty($this->limit)) {
          return ' LIMIT 18446744073709551615 OFFSET ' . $offset;
        }
      }

      return ' OFFSET ' . $offset;
    }

    return '';
  }

  /**
   * Execute a wpdb->get_results() query.
   *
   * @return array
   */
  protected function getSQLResults($sql, $type = ARRAY_A)
  {
    $results = $this->wpdb->get_results($sql, $type);

    return $results;
  }

  /**
   * Return the last record that matches the given where conditions.
   *
   * @return Model
   */
  public function last()
  {
    $this->limit(1);
    $this->orderBy($this->primaryKey, 'desc');
    $this->all();

    return $this->collection->first();
  }

  /**
   * Set the limit "limit" clause for the query.
   */
  public function limit($value = 1)
  {
    $this->limit = max(1, (int) $value);

    return $this;
  }

  /**
   * Set the "order by" clause for the query.
   */
  public function orderBy($column, $order = 'asc')
  {
    $order = strtolower($order);

    $this->orders[] = [$column, $order];

    return $this;
  }

  /**
   * Return the first record that matches the given where conditions.
   *
   * @return Model
   */
  public function first()
  {
    $this->limit(1);
    $this->all();

    return $this->collection->first();
  }

  /**
   * Return the record with the given id.
   *
   * @return Model
   */
  public function find($id)
  {
    $this->where($this->primaryKey, $id);

    return $this->first();
  }

  /**
   * Add a where condition to the query.
   *
   * @param string $column   The column name.
   * @param string $operator The operator.
   * @param mixed  $value    The value.
   * @param string $boolean  The boolean operator.
   */
  public function where($column, $operator = null, $value = null, $boolean = 'and')
  {
    if (is_array($column)) {
      if (count($column) !== count($column, COUNT_RECURSIVE)) {
        foreach ($column as $where_array) {
          $this->where($where_array);
        }
      } else {
        if (count($column) == 2) {
          [$c, $v] = $column;
          $this->where($c, $v);
        } else {
          [$c, $o, $v] = $column;
          $this->where($c, $o, $v);
        }
      }

      return $this;
    }

    [$value, $operator] = $this->prepareValueAndOperator($value, $operator, func_num_args() === 2);

    $this->wheres[] = compact('column', 'operator', 'value', 'boolean');

    //error_log(print_r($this->wheres, true));
    //error_log(print_r($this->getWhere(), true));

    return $this;
  }

  /**
   * Return the value and operator for the query.
   *
   * @param mixed  $value
   * @param string $operator
   * @param bool   $useDefault
   *
   * @return array
   */
  private function prepareValueAndOperator($value, $operator, $useDefault = false)
  {
    if ($useDefault) {
      return [$operator, '='];
    } elseif ($this->invalidOperatorAndValue($operator, $value)) {
      throw new InvalidArgumentException('Illegal operator and value combination.');
    }

    return [$value, $operator];
  }

  /**
   * Determine if the given operator and value combination is legal.
   *
   * Prevents using Null values with invalid operators.
   *
   * @param string $operator
   * @param mixed  $value
   * @return bool
   */
  protected function invalidOperatorAndValue($operator, $value)
  {
    return is_null($value) && in_array($operator, $this->operators) && !in_array($operator, ['=', '<>', '!=']);
  }

  /**
   * Return the values of a single column.
   *
   * @param string $column_name
   * @return array
   */
  public function pluck($column_name)
  {
    $this->select($column_name);
    $this->all();

    return array_map(function ($item) use ($column_name) {
      return $item->$column_name;
    }, $this->collection->getArrayCopy());
  }

  /**
   * Set the select columns for the query.
   *
   * @param array|string $columns The columns to select.
   */
  public function select($columns = [])
  {
    $columns = is_array($columns) ? $columns : func_get_args();
    $this->select_columns = $columns;

    return $this;
  }

  /**
   * Delete one or more records.
   */
  public function delete()
  {
    $sql = 'DELETE ' . "FROM `{$this->table}`" . $this->getWhere();

    $this->wpdb->query($sql);

    return $this;
  }

  /**
   * Execute a wpdb->query() query.
   *
   * @return mixed
   */
  protected function query($sql)
  {
    return $this->wpdb->query($sql);
  }

  /**
   * Add an "or where" condition to the query.
   */
  public function orWhere($column, $operator = null, $value = null, $boolean = 'or')
  {
    if (is_array($column)) {
      if (count($column) !== count($column, COUNT_RECURSIVE)) {
        foreach ($column as $where_array) {
          $this->orWhere($where_array);
        }
      } else {
        if (count($column) == 2) {
          [$c, $v] = $column;
          $this->orWhere($c, $v);
        } else {
          [$c, $o, $v] = $column;
          $this->orWhere($c, $o, $v);
        }
      }

      return $this;
    }

    [$value, $operator] = $this->prepareValueAndOperator($value, $operator, func_num_args() === 2);

    $this->wheres[] = compact('column', 'operator', 'value', 'boolean');

    //error_log(print_r($this->wheres, true));
    //error_log(print_r($this->getWhere(), true));

    return $this;
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
   * Add a where IN condition to the query.
   *
   * @param string       $column The column name.
   * @param array|string $value  The values.
   */
  public function orWhereIn($column, $value)
  {
    return $this->whereIn($column, $value, 'or');
  }

  /**
   * Add a where IN condition to the query.
   *
   * @param string       $column The column name.
   * @param string|array $value
   * @param string       $boolean
   * @return \WPKirk\WPBones\Database\QueryBuilder
   */
  public function whereIn($column, $value, $boolean = 'and')
  {
    $value = is_string($value) ? explode(',', $value) : $value;
    $operator = 'IN';
    $this->wheres[] = compact('column', 'operator', 'value', 'boolean');

    return $this;
  }

  /**
   * Add a where NOT IN condition to the query.
   *
   * @param string       $column The column name.
   * @param string|array $value  The values.
   */
  public function orWhereNotIn($column, $value)
  {
    return $this->whereNotIn($column, $value, 'or');
  }

  /**
   * Add a where NOT IN condition to the query.
   *
   * @param string       $column The column name.
   * @param array|string $value  The values.
   */
  public function whereNotIn($column, $value, $boolean = 'and')
  {
    $value = is_string($value) ? explode(',', $value) : $value;
    $operator = 'NOT IN';
    $this->wheres[] = compact('column', 'operator', 'value', 'boolean');

    return $this;
  }

  /**
   * Add a where BETWEEN condition to the query.
   *
   * @param string       $column The column name.
   * @param array|string $value  The values.
   */
  public function orWhereBetween($column, $value)
  {
    return $this->whereBetween($column, $value, 'or');
  }

  /**
   * Add a where BETWEEN condition to the query.
   *
   * @param string       $column The column name.
   * @param array|string $value  The values.
   */
  public function whereBetween($column, $value, $boolean = 'and')
  {
    $value = is_string($value) ? explode(',', $value) : $value;
    $operator = 'BETWEEN';
    $this->wheres[] = compact('column', 'operator', 'value', 'boolean');

    return $this;
  }

  /**
   * Add a where NOT BETWEEN condition to the query.
   *
   * @param string       $column The column name.
   * @param array|string $value  The values.
   */
  public function orWhereNotBetween($column, $value)
  {
    return $this->whereNotBetween($column, $value, 'or');
  }

  /**
   * Add a where NOT BETWEEN condition to the query.
   *
   * @param string       $column The column name.
   * @param array|string $value  The values.
   */
  public function whereNotBetween($column, $value, $boolean = 'and')
  {
    $value = is_string($value) ? explode(',', $value) : $value;
    $operator = 'NOT BETWEEN';
    $this->wheres[] = compact('column', 'operator', 'value', 'boolean');

    return $this;
  }

  /**
   * Set the offset "limit" clause for the query.
   */
  public function offset($value = 0)
  {
    $this->offset = max(0, (int) $value);

    return $this;
  }

  /**
   * Return a single column's value from the first result of the query.
   *
   * @return mixed
   */
  public function value($attribute)
  {
    return $this->first()->$attribute;
  }

  /**
   * Return the count of the records that match the given where conditions.
   *
   * @return int
   */
  public function count()
  {
    $sql =
      'SELECT COUNT(*) ' .
      "FROM `{$this->table}`" .
      $this->getWhere() .
      $this->getOrderBy() .
      $this->getLimit() .
      $this->getOffset();

    return $this->var($sql);
  }

  /**
   * Execute a wpdb->get_var() query.
   *
   * @return mixed
   */
  protected function var($sql)
  {
    return $this->wpdb->get_var($sql);
  }

  /*
     |--------------------------------------------------------------------------
     | wpdb wrapper
     |--------------------------------------------------------------------------
     |
     |
     */

  /**
   * Insert one or more records.
   *
   * @param array $values The data to insert.
   *
   * @return int|array The inserted id.
   */
  public function insert($values)
  {
    // here we can get a single or multiple array of values
    if (count($values) !== count($values, COUNT_RECURSIVE)) {
      $ids = [];
      foreach ($values as $value) {
        $ids[] = $this->insert($value);
      }

      return $ids;
    }

    $columns = array_keys($values);

    [$columns, $values] = $this->getColumnsAndValues($values);

    $sql = "INSERT INTO `{$this->table}` " . "($columns) " . 'VALUES ' . "($values)";

    $this->query($sql);

    return $this->wpdb->insert_id;
  }

  /**
   * Return column and value for the query.
   *
   * @param array $values
   * @return array
   */
  private function getColumnsAndValues($values): array
  {
    $columns = array_keys($values);
    $columns_string = implode(',', $columns);
    $values_string = implode(
      ',',
      array_map(function ($value) {
        return "'" . $this->wpdb->_real_escape($value) . "'";
      }, $values)
    );

    return [$columns_string, $values_string];
  }

  /**
   * Update one or more records.
   *
   * @param array $values The data to update.
   */
  public function update($values)
  {
    $set = implode(
      ',',
      array_map(function ($key) use ($values) {
        return "`$key` = " . (is_numeric($values[$key]) ? $values[$key] : "'$values[$key]'");
      }, array_keys($values))
    );

    $sql = "UPDATE `{$this->table}` " . "SET {$set} " . $this->getWhere();

    return $this->query($sql);
  }

  /*
     |--------------------------------------------------------------------------
     | Getters and Setters
     |--------------------------------------------------------------------------
     |
     | Special Getters and Setters for the model.
     |
     */

  /**
   * Truncate the table.
   */
  public function truncate(): QueryBuilder
  {
    $sql = "TRUNCATE TABLE `{$this->table}`";
    $this->query($sql);

    return $this;
  }

  /**
   * Return the primary key.
   *
   * @return string
   */
  public function getPrimaryKey(): string
  {
    return $this->primaryKey;
  }

  /**
   * Set the primary key.
   */
  public function setPrimaryKey($primaryKey)
  {
    $this->primaryKey = $primaryKey;
  }

  /**
   * Return the table name without the prefix.
   *
   * @return string
   */
  public function getTable(): string
  {
    return $this->table;
  }

  /**
   * Set the table name without the prefix.
   *
   * @param string $table The table name without the prefix.
   */
  public function setTable($table)
  {
    $this->table = $table;
  }

  public function getParentModel()
  {
    return $this->parentModel;
  }

  public function setParentModel($parentModel)
  {
    $this->parentModel = $parentModel;
  }
}
