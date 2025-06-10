<?php

namespace Ondapresswp\WPBones\Database;

use Exception;

/**
 * Class Seeder
 *
 * Currently, we're using the WordPress wpdb object.
 * In the future, would be nice to use a custom DB class.
 *
 */
abstract class Seeder
{
  /**
   * The database table name with WordPress prefix.
   * Usually, you should use the model name as the table name.
   * It will be converted to lowercase and with the WordPress prefix.
   *
   * @var string
   */
  protected $tablename;

  /**
   * Will use the WordPress prefix of the database.
   *
   * @since 1.7.0
   * @var bool
   */
  protected $usePrefix = true;

  /**
   * The WordPress database object.
   */
  protected $wpdb;

  /**
   * This flag is used to run the seeder only once.
   *
   * @var bool
   */
  protected $runOnce = false;

  /**
   * Run the database seeds.
   *
   * @throws \Exception
   */
  public function __construct()
  {
    global $wpdb;

    $this->wpdb = $wpdb;

    if (empty($this->tablename)) {
      throw new Exception('The tablename property is not set.');
    }

    $this->tablename = DB::getTableName($this->tablename, $this->usePrefix);

    if ($this->runOnce && $this->count() > 0) {
      return;
    }

    $this->run();
  }

  /**
   * Return the number of rows in the table.
   *
   * @return int
   */
  protected function count(): int
  {
    return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tablename}");
  }

  /**
   * Run the database seeds.
   * You have to override this method in your seeder class.
   */
  abstract public function run();

  /**
   * Insert a row into a table.
   *
   * @param string $sql The SQL statement.
   */
  protected function insert($sql)
  {
    return $this->wpdb->query("INSERT INTO `{$this->tablename}` {$sql}");
  }

  /**
   * Execute any SQL statement.
   *
   * @param string $sql The SQL statement to execute.
   */
  protected function query($sql)
  {
    return $this->wpdb->query($sql);
  }

  /**
   * Truncate a table.
   *
   * @param string $tablename Optional. The table name without the WordPress prefix.
   *                          If not specified, the tablename property will be used.
   */
  protected function truncate($tablename = '')
  {
    if (empty($tablename)) {
      return $this->wpdb->query("TRUNCATE TABLE {$this->tablename}");
    }

    $table = DB::getTableName($tablename);

    return $this->wpdb->query("TRUNCATE TABLE {$table}");
  }
}
