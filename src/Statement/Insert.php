<?php

namespace CodesVault\Howdyqb\Statement;

use CodesVault\Howdyqb\Api\InsertInterface;
use CodesVault\Howdyqb\QueryFactory;
use CodesVault\Howdyqb\SqlGenerator;
use CodesVault\Howdyqb\Utilities;

class Insert
{
    protected $db;
    protected $data = [];
    public $sql = [];
    public $test = [];
    protected $params = [];
    private $table_name;

    public function __construct($db, string $table_name, array $data)
    {
        $this->db = $db;
        $this->data = $data;
        $this->table_name = $table_name;

        $this->start();
        $this->sql['table_name'] = $this->get_table_name();
        $this->sql['columns'] = $this->get_columns();
        $this->sql['value_placeholders'] = $this->get_value_placeholders();
        $this->params = $this->get_params();
        
        $this->insert_data();
    }

    private function insert_data()
    {
        $query = SqlGenerator::insert($this->sql);

        try {
            $this->driver_exicute($query);
        } catch (\Exception $exception) {
            Utilities::throughException($exception);
        }
    }

    private function driver_exicute($sql)
    {
        $driver = $this->db;
        if ('wpdb' === QueryFactory::getDriver()) {
            return $driver->query($driver->prepare($sql, $this->params));
        }

        $data = $driver->prepare($sql);
        return $data->execute($this->params);
    }

    private function start()
    {
        $this->sql['start'] = 'INSERT INTO';
    }

    private function get_table_name()
    {
        // global $wpdb;
        // return $wpdb->prefix . $this->table_name;
        if (empty(QueryFactory::getConfig())) {
            global $wpdb;
            return $wpdb->prefix . $this->table_name;
        } else {
            return QueryFactory::getConfig()->prefix . $this->table_name;
        }
    }

    private function get_columns()
    {
        if (empty($this->data)) return;

        $columns = [];
        foreach ($this->data[0] as $column => $value) {
            $columns[] = $column;
        }
        return '(' . implode(', ', $columns) . ')';
    }

    private function get_value_placeholders()
    {
        $placeholders = [];

        if (count($this->data) > 1) {
            foreach ($this->data as $row) {
                $placeholders[] = '(' . implode(',', array_fill(0, count($row), Utilities::get_placeholder())) . ')';
            }
        } else {
            $placeholders[] = '(' . implode(',', array_fill(0, count($this->data[0]), Utilities::get_placeholder())) . ')';
        }
        return 'VALUES ' . implode(',', $placeholders);
    }

    private function get_params()
    {
        $params = [];
        foreach ($this->data as $value) {
            foreach ($value as $val) {
                $params[] = $val;
            }
        }
        return $params;
    }
}
