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

class Mysql
{
    protected $app;
    protected $dbh;
    protected $database;
    public function __construct(Application $app, Database $database){
        $this->app = $app;
        $this->database = $database;
    }
    private function dsn($raw = false)
    {
        $port = $this->app->getDb('hostport');
        if($raw){
            if($port != 3306){
                return 'mysql:host=' . $this->app->getDb('hostname') . ';port=' . $port . ';charset=utf8';
            }
            else{
                return 'mysql:host=' . $this->app->getDb('hostname') . ';charset=utf8';
            }
        }
        else{
            if($port != 3306){
                return 'mysql:host=' . $this->app->getDb('hostname') . ';port=' . $port . ';dbname=' . $this->app->getDb('database') . ';charset=utf8';
            }
            else{
                return 'mysql:host=' . $this->app->getDb('hostname') . ';dbname=' . $this->app->getDb('database') . ';charset=utf8';
            }
        }
    }
    public function disconnect()
    {
        $this->dbh = null;
    }
    public function connect($raw = false)
    {
        if(!is_null($this->dbh)){
            return $this->dbh;
        }
        else{
            try {
                $this->dbh = new PDO($this->dsn($raw), $this->app->getDb('username'), $this->app->getDb('password'));
                return $this->dbh;
                
            }
            catch(\PDOException $e){
                throw new PDOExecutionException('Database connection failed: ' . $e->getMessage(), $e);
            }
        }
    }
    public function hasDb($name)
    {
        $sql = 'show databases';
        $result = $this->database->sqlRaw($sql);
        foreach($result as $key => $val){
            if($val['Database'] == $name){
                return true;
            }
        }
        return false;
    }
    public function newDb($name)
    {
        $result = true;
        if(!$this->hasDb($name)){
            $sql = 'CREATE DATABASE IF NOT EXISTS `' . $name . '` DEFAULT CHARSET utf8 COLLATE utf8_general_ci';
            $result = $this->database->sqlRaw($sql);
        }
        return $result;
    }
    public function newTable($tableName, $tableArray)
    {
        $sql = '';
        $indexs = '';
        foreach($tableArray as $item){
            $index = '';
            if(strtoupper($item['type']) == 'AUTO'){
                $field = '`' . $item['name'] . '` int(11) unsigned NOT NULL AUTO_INCREMENT';
                $index = 'PRIMARY KEY (`' . $item['name'] . '`)';
            }
            else{
                $field = '`' . $item['name'] . '` ' . $item['type'];
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
                    $index = 'KEY `' . $item['index'] . '` (`' . $item['name'] . '`)';
                }
            }
            $sql .= $field . ',';
            if(!empty($index)){
                $indexs .= $index . ',';
            }
        }
        $sql .= $indexs;
        $sql = 'CREATE TABLE `' . $this->app->getDb('prefix') . $tableName . '` (' . rtrim($sql, ',') . ');';
        try{
            $this->database->sql($sql);
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