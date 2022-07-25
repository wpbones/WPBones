<?php

namespace WPKirk\WPBones\Database;

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
  protected $tablename = 'dummy_table_name';

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
   */
  public function __construct()
  {
    global $wpdb;

    $this->wpdb = $wpdb;

    $this->tablename = DB::getTableName($this->tablename);

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
   * @param string $tablename The table name.
   */
  protected function truncate($tablename = "")
  {
    if (empty($tablename)) {
      return $this->wpdb->query("TRUNCATE TABLE {$this->tablename}");
    }

    $table = DB::getTableName($tablename);

    return $this->wpdb->query("TRUNCATE TABLE {$table}");
  }
}
