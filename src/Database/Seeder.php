<?php

namespace WPKirk\WPBones\Database;

/**
 * Class Seeder
 *
 * TODO: Use a future DB class
 * Currently we're using the WordPress wpdb object.
 * In the future, would be nice to use a custom DB class.
 *
 */
abstract class Seeder
{
    /**
     * The database table name.
     *
     * TODO: evaluate if this should be renamed to table like in the Model class.
     *
     * @var string
     */
    protected $tablename = null;

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

        if ($this->runOnce && $this->count() > 0) {
            return;
        }

        $this->run();
    }

    /**
     * Run the database seeds.
     * You have to override this method in your seeder class.
     */
    abstract public function run();

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
     * Retrun the number of rows in the table.
     *
     * @return int
     */
    protected function count()
    {
        return $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->getTableName()}");
    }

    /**
     * Insert a row into a table.
     *
     * @param  string $sql The SQL statement.
     */
    protected function insert($sql)
    {
        return $this->wpdb->query("INSERT INTO `{$this->getTableName()}` {$sql}");
    }

    /**
     * Truncate a table.
     *
     * @param  string $table The table name.
     */
    protected function truncate($tablename = "")
    {
        if (empty($tablename) && is_null($this->tablename)) {
            return null;
        }

        $this->tablename = $tablename ?: $this->tablename;

        return $this->wpdb->query("TRUNCATE TABLE {$this->getTableName()}");
    }

    /**
     * Retrun the tablename with WordPress prefix.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->wpdb->prefix . $this->tablename;
    }
}
