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
   * Table descriptions (the result of DESC) already fetched during this request,
   * keyed by table name. Fetched lazily by getColumns(), never by the constructor.
   *
   * @var array<string, array<int, array<string, mixed>>>
   */
  private static $descriptions = [];

  /**
   * Order directions accepted by orderBy().
   *
   * @var string[]
   */
  private const DIRECTIONS = ['asc', 'desc'];

  /**
   * A plain column identifier, optionally table-qualified: `column` or `table.column`.
   * Expressions are refused on purpose: identifiers are quoted, never escaped.
   */
  private const IDENTIFIER = '/^[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)?$/D';

  /**
   * A table name including the WordPress prefix. WordPress itself limits $table_prefix
   * to letters, digits and underscores; plugin tables follow the same rule.
   */
  private const TABLE = '/^[A-Za-z0-9_]+$/D';

  /**
   * A value that can be emitted bare as a numeric literal: an optional sign, digits,
   * an optional decimal part, nothing else (the D modifier refuses a trailing newline).
   */
  private const NUMERIC = '/^-?\d+(?:\.\d+)?$/D';

  /**
   * Boolean connectors accepted between where clauses.
   *
   * @var string[]
   */
  private const BOOLEANS = ['and', 'or'];

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
    $this->table = $this->validateTable(DB::getTableName($table, $usePrefix));
    $this->primaryKey = $primaryKey;
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
   * Fetch the table description with a DESC query.
   *
   * Do not call this directly: getColumns() caches the result per table for the whole
   * request, so a Model that instantiates a builder per static call does not pay one
   * round trip each time.
   *
   * @return array<int, array<string, mixed>>
   */
  protected function getTableDescription(): array
  {
    $columns = [];

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

      foreach ((array) $desc as $column) {
        $columns[] = [
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

    return $columns;
  }

  /**
   * Return the table description, fetched once per table per request.
   *
   * @return array<int, array<string, mixed>> One entry per column: name, type, null, key, default, extra.
   */
  public function getColumns(): array
  {
    if (empty($this->table)) {
      return [];
    }

    if (!array_key_exists($this->table, self::$descriptions)) {
      self::$descriptions[$this->table] = $this->getTableDescription();
    }

    return self::$descriptions[$this->table];
  }

  /**
   * Forget every cached table description.
   *
   * Needed after a migration changes a table within the same request, and by tests.
   */
  public static function flushDescriptionCache(): void
  {
    self::$descriptions = [];
  }

  /**
   * Refuse a table name that could not be interpolated safely inside backticks.
   *
   * @throws InvalidArgumentException
   */
  private function validateTable(string $table): string
  {
    $table = trim($table);

    if (!preg_match(self::TABLE, $table)) {
      throw new InvalidArgumentException(sprintf('Invalid table name "%s".', $table));
    }

    return $table;
  }

  /**
   * Normalize the value list of whereIn()/whereBetween() and their variants: an array,
   * or a comma-separated string whose parts are trimmed. Anything else is refused.
   *
   * @param mixed $value
   * @throws InvalidArgumentException
   */
  private function normalizeList($value, string $method): array
  {
    if (is_string($value)) {
      return array_map('trim', explode(',', $value));
    }

    if (!is_array($value)) {
      throw new InvalidArgumentException(sprintf('%s() expects an array or a comma-separated string, %s given.', $method, gettype($value)));
    }

    return $value;
  }

  /**
   * Return one scalar as a safe SQL literal.
   *
   * Integers, floats and strictly decimal strings are emitted bare, booleans as 1/0,
   * null as NULL; everything else goes through $wpdb->_real_escape() and is quoted.
   * Arrays are refused: IN and BETWEEN unpack theirs before getting here.
   *
   * @param mixed $value
   * @throws InvalidArgumentException
   */
  private function formatScalar($value): string
  {
    if (is_array($value)) {
      throw new InvalidArgumentException('A value cannot be an array here; use whereIn() or whereBetween().');
    }

    if ($value === null) {
      return 'NULL';
    }

    if (is_bool($value)) {
      return $value ? '1' : '0';
    }

    if (is_int($value) || is_float($value)) {
      return (string) $value;
    }

    $value = (string) $value;

    if (preg_match(self::NUMERIC, $value)) {
      return $value;
    }

    return "'" . $this->wpdb->_real_escape($value) . "'";
  }

  /**
   * Normalize the connector between where clauses to `and` or `or`; refuse anything else.
   *
   * @param mixed $boolean
   * @throws InvalidArgumentException
   */
  private function normalizeBoolean($boolean): string
  {
    $normalized = is_scalar($boolean) ? strtolower(trim((string) $boolean)) : '';

    if (!in_array($normalized, self::BOOLEANS, true)) {
      throw new InvalidArgumentException(sprintf('Boolean connector must be "and" or "or", "%s" given.', is_scalar($boolean) ? (string) $boolean : gettype($boolean)));
    }

    return $normalized;
  }

  /**
   * Quote a column identifier with backticks.
   *
   * Only `column` and `table.column` are accepted. Anything else — an expression, a
   * function call, a semicolon — is refused, because an identifier cannot be escaped,
   * only validated. Use the dedicated methods (count(), select() with aliases) instead.
   *
   * @param mixed $identifier
   * @throws InvalidArgumentException
   */
  private function quoteIdentifier($identifier): string
  {
    $identifier = is_scalar($identifier) ? trim((string) $identifier) : '';

    if (!preg_match(self::IDENTIFIER, $identifier)) {
      throw new InvalidArgumentException(sprintf('Invalid column identifier "%s".', $identifier));
    }

    return '`' . str_replace('.', '`.`', $identifier) . '`';
  }

  /**
   * Quote one entry of a select list: `*`, `table.*`, `column`, `table.column`,
   * or `column as alias`.
   *
   * @param mixed $column
   * @throws InvalidArgumentException
   */
  private function quoteSelectColumn($column): string
  {
    $column = is_scalar($column) ? trim((string) $column) : '';

    if ($column === '*') {
      return '*';
    }

    if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\.\*$/D', $column, $m)) {
      return '`' . $m[1] . '`.*';
    }

    if (preg_match('/^(\S+)\s+as\s+([A-Za-z_][A-Za-z0-9_]*)$/iD', $column, $m)) {
      return $this->quoteIdentifier($m[1]) . ' AS `' . $m[2] . '`';
    }

    return $this->quoteIdentifier($column);
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
    $columns = $this->select_columns ?: (is_array($columns) ? $columns : func_get_args());
    sort($columns);
    $column_string = implode(',', array_map([$this, 'quoteSelectColumn'], $columns));

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
    if ($this->wheres === []) {
      return ' WHERE 1 ';
    }

    $sql = ' WHERE ';

    foreach ($this->wheres as $index => $where_item) {
      $boolean = strtoupper($this->normalizeBoolean($where_item['boolean']));
      $column = $this->quoteIdentifier($where_item['column']);
      $operator = $this->getWhereOperator($where_item['operator']);
      $value = $where_item['value'];

      if ($value === [] && in_array($operator, ['IN', 'NOT IN'], true)) {
        // whereIn([]) can never match, whereNotIn([]) always does — same as Laravel.
        $clause = $operator === 'IN' ? '0 = 1' : '1 = 1';
      } elseif ($value === null && in_array($operator, ['=', '<>', '!='], true)) {
        // `col = NULL` is never true in SQL; a null value means IS [NOT] NULL.
        $clause = $column . ($operator === '=' ? ' IS NULL' : ' IS NOT NULL');
      } else {
        $clause = $column . ' ' . $operator . ' ' . $this->getWhereValue($value, $operator);
      }

      // The first clause takes no connector: `WHERE 1 OR x` would match every row,
      // so orWhere() as the opening clause used to turn delete() into a truncate.
      $sql .= ($index === 0 ? '' : $boolean . ' ') . $clause . ' ';
    }

    return $sql;
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
    if (is_array($value) && in_array($operator, ['IN', 'NOT IN'], true)) {
      return '(' . implode(',', $this->getFormatValue(array_values($value))) . ')';
    }

    if (is_array($value) && in_array($operator, ['BETWEEN', 'NOT BETWEEN'], true)) {
      if (count($value) !== 2) {
        throw new InvalidArgumentException(sprintf('%s needs exactly two values, %d given.', $operator, count($value)));
      }

      return implode(' AND ', $this->getFormatValue(array_values($value)));
    }

    if (is_array($value)) {
      throw new InvalidArgumentException(sprintf('Operator "%s" does not accept an array value.', $operator));
    }

    return $this->getFormatValue($value);
  }

  /**
   * Return a value as a safe SQL literal.
   *
   * Integers, floats and strictly decimal strings are emitted bare; everything else is
   * escaped through $wpdb->_real_escape() and quoted. Arrays are formatted element by
   * element.
   *
   * @param mixed $value The value to format.
   * @return string|string[]
   */
  private function getFormatValue($value)
  {
    if (is_array($value)) {
      return array_map([$this, 'formatScalar'], $value);
    }

    return $this->formatScalar($value);
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
    // null and '' used to mean the default direction; keep that.
    $order = strtolower(trim((string) $order)) ?: 'asc';

    if (!in_array($order, self::DIRECTIONS, true)) {
      throw new InvalidArgumentException(sprintf('Order direction must be "asc" or "desc", "%s" given.', $order));
    }

    $this->orders[] = [$this->quoteIdentifier($column), $order];

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
    }

    $normalized = strtolower(trim((string) $operator));

    if (!in_array($normalized, $this->operators, true)) {
      throw new InvalidArgumentException(sprintf('Illegal operator "%s".', (string) $operator));
    }

    if ($this->invalidOperatorAndValue($normalized, $value)) {
      throw new InvalidArgumentException('Illegal operator and value combination.');
    }

    return [$value, $normalized];
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
    $value = $this->normalizeList($value, __FUNCTION__);
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
    $value = $this->normalizeList($value, __FUNCTION__);
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
    $value = $this->normalizeList($value, __FUNCTION__);
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
    $value = $this->normalizeList($value, __FUNCTION__);
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
    // Several rows: every entry is itself a row. A single row with an array value is
    // refused later by formatScalar(), instead of being mistaken for a row list.
    if ($values !== [] && count(array_filter($values, 'is_array')) === count($values)) {
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
    $columns_string = implode(',', array_map([$this, 'quoteIdentifier'], array_keys($values)));
    $values_string = implode(',', array_map([$this, 'formatScalar'], array_values($values)));

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
        return $this->quoteIdentifier($key) . ' = ' . $this->formatScalar($values[$key]);
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
    $this->table = $this->validateTable(is_scalar($table) ? (string) $table : '');
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
