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
use PDO;
use jsnpp\exception\PDOExecutionException;

class Sqlite
{
    protected $app;
    protected $dbh;
    private $sqlitePath;
    public function __construct(Application $app){
        $this->app = $app;
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
}