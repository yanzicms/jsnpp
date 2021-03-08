<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

use PDO;

class Database
{
    protected $app;
    private $dbh;
    public function __construct(Application $app){
        $this->app = $app;
    }
    private function connectDb($raw = false)
    {
        if(is_null($this->dbh)){
            $dbtype = $this->app->getDb('type');
            $dbtype = 'jsnpp\db\\' . ucfirst(strtolower(trim($dbtype)));
            $this->dbh = $this->app->get($dbtype)->connect($raw);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }
    public function hasDb($name)
    {
        $dbtype = $this->app->getDb('type');
        $dbtype = 'jsnpp\db\\' . ucfirst(strtolower(trim($dbtype)));
        return $this->app->get($dbtype)->hasDb($name);
    }
    public function newDb($name)
    {
        $dbtype = $this->app->getDb('type');
        $dbtype = 'jsnpp\db\\' . ucfirst(strtolower(trim($dbtype)));
        return $this->app->get($dbtype)->newDb($name);
    }
    public function disconnect()
    {
        $dbtype = $this->app->getDb('type');
        $dbtype = 'jsnpp\db\\' . ucfirst(strtolower(trim($dbtype)));
        $this->app->get($dbtype)->disconnect();
        $this->dbh = null;
    }
    public function beginTransaction()
    {
        $this->dbh->beginTransaction();
    }
    public function endTransaction()
    {
        $this->dbh->commit();
    }
    public function rollBack()
    {
        $this->dbh->rollBack();
    }
    public function sql($sql, $data = [])
    {
        $sql = trim($sql);
        $this->connectDb();
        $sth = $this->dbh->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $result = $sth->execute($data);
        if(strtolower(substr($sql, 0, 4)) == 'show' || strtolower(substr($sql, 0, 6)) == 'select'){
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        }
        elseif(strtolower(substr($sql, 0, 6)) == 'insert'){
            $result = $this->dbh->lastInsertId();
        }
        return $result;
    }
    public function sqlRaw($sql, $data = [])
    {
        $sql = trim($sql);
        $this->connectDb(true);
        $sth = $this->dbh->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $result = $sth->execute($data);
        if(strtolower(substr($sql, 0, 4)) == 'show' || strtolower(substr($sql, 0, 6)) == 'select'){
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }
}