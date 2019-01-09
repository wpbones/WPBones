<?php

namespace WPKirk\WPBones\Database;

class Seeder
{

    protected $tablename = null;

    public function __construct()
    {
        $this->run();

    }

    public function run()
    {
        // you have to override this method
    }

    protected function query($sql)
    {
        global $wpdb;

        return $wpdb->query($sql);
    }

    protected function insert($sql)
    {
        global $wpdb;

        $tablename = $wpdb->prefix . $this->tablename;

        return $wpdb->query("INSERT INTO `{$tablename}` {$sql}");
    }

    protected function truncate($tablename = "")
    {
        global $wpdb;

        if (empty($tablename) && is_null($this->tablename)) {
            return null;
        }

        if (empty($tablename)) {
            $tablename = $this->tablename;
        }

        $tablename = $wpdb->prefix . $tablename;

        return $wpdb->query("TRUNCATE TABLE {$tablename}");
    }
}