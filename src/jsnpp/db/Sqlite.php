<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp\db;

use jsnpp\Application;
use jsnpp\Database;
use PDO;
use jsnpp\exception\PDOExecutionException;

class Sqlite
{
    protected $app;
    protected $dbh;
    protected $database;
    private $sqlitePath;
    public function __construct(Application $app, Database $database){
        $this->app = $app;
        $this->database = $database;
        $this->sqlitePath = $this->app->appDir() . DIRECTORY_SEPARATOR . 'sqlite';
    }
    private function dsn($dbname = '')
    {
        if(empty($dbname)){
            $dbname = $this->app->getDb('database');
        }
        if(strpos($dbname, '.') === false){
            $dbname = $dbname . '.db';
        }
        if(!is_dir($this->sqlitePath)){
            @mkdir($this->sqlitePath, 0777, true);
        }
        return 'sqlite:' . $this->sqlitePath . DIRECTORY_SEPARATOR . $dbname;
    }
    public function disconnect()
    {
        $this->dbh = null;
    }
    public function connect($dbname = '')
    {
        if(!is_null($this->dbh)){
            return $this->dbh;
        }
        else{
            try {
                $this->dbh = new PDO($this->dsn($dbname));
                return $this->dbh;
            }
            catch(\PDOException $e){
                throw new PDOExecutionException('Database connection failed: ' . $e->getMessage(), $e);
            }
        }
    }
    public function hasDb($name)
    {
        if(strpos($name, '.') === false){
            $name = $name . '.db';
        }
        $sqliteflie = $this->sqlitePath . DIRECTORY_SEPARATOR . $name;
        if(is_file($sqliteflie)){
            return true;
        }
        return false;
    }
    public function newDb($name)
    {
        if(strpos($name, '.') === false){
            $name = $name . '.db';
        }
        $result = false;
        $this->connect($name);
        if(!is_null($this->dbh)){
            $result = true;
        }
        return $result;
    }
    public function newTable($tableName, $tableArray)
    {
        $sql = '';
        $indexs = [];
        foreach($tableArray as $item){
            $index = '';
            if(strtoupper($item['type']) == 'AUTO'){
                $field = $item['name'] . ' INTEGER PRIMARY KEY AUTOINCREMENT';
            }
            elseif(strtoupper($item['type']) == 'UINT'){
                $field = $item['name'] . ' INT NOT NULL DEFAULT 0';
                if(isset($item['index'])){
                    $index = 'CREATE INDEX ' . $item['name'] . '_' . $item['index'] . ' ON ' . $this->app->getDb('prefix') . $tableName . ' (' . $item['name'] . ');';
                }
            }
            else{
                $field = $item['name'] . ' ' . $item['type'];
                if(isset($item['length'])){
                    $field .= '(' . $item['length'] . ')';
                }
                if(isset($item['notnull']) && $item['notnull']){
                    $field .= ' NOT NULL';
                }
                if(isset($item['default'])){
                    if(is_numeric($item['default'])){
                        $field .= ' DEFAULT ' . $item['default'];
                    }
                    else{
                        $field .= ' DEFAULT \'' . $item['default'] . '\'';
                    }
                }
                if(isset($item['index'])){
                    $index = 'CREATE INDEX ' . $item['name'] . '_' . $item['index'] . ' ON ' . $this->app->getDb('prefix') . $tableName . ' (' . $item['name'] . ');';
                }
            }
            $sql .= $field . ',';
            if(!empty($index)){
                $indexs[] = $index;
            }
        }
        $sql = 'CREATE TABLE ' . $this->app->getDb('prefix') . $tableName . '(' . rtrim($sql, ',') . ');';
        try{
            $this->database->sql($sql);
            foreach($indexs as $idx){
                $this->database->sql($idx);
            }
            return true;
        }
        catch(\Exception $e){
            return $e->getMessage();
        }
    }
    public function deleteTable($tableName)
    {
        $sql = 'DROP TABLE ' . $this->app->getDb('prefix') . $tableName;
        try{
            $this->database->sql($sql);
            return true;
        }
        catch(\Exception $e){
            return $e->getMessage();
        }
    }
}