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
        $re = $this->app->get($dbtype)->hasDb($name);
        $this->disconnect();
        return $re;
    }
    public function newDb($name)
    {
        $dbtype = $this->app->getDb('type');
        $dbtype = 'jsnpp\db\\' . ucfirst(strtolower(trim($dbtype)));
        $re = $this->app->get($dbtype)->newDb($name);
        $this->disconnect();
        return $re;
    }
    
    /**
     * 新建表
     * @param  string  $tableName
     * @param  array  $tableArray
     * @param  string|null  $charset
     * @return bool
     */
    public function newTable($tableName, $tableArray, $charset = null)
    {
        $dbtype = $this->app->getDb('type');
        $dbtype = 'jsnpp\db\\' . ucfirst(strtolower(trim($dbtype)));
        $re = $this->app->get($dbtype)->newTable($tableName, $tableArray, $charset);
        return $re;
    }
    /**
     * 删除表
     * @param  string  $tableName
     * @return bool
     */
    public function deleteTable($tableName)
    {
        $dbtype = $this->app->getDb('type');
        $dbtype = 'jsnpp\db\\' . ucfirst(strtolower(trim($dbtype)));
        $re = $this->app->get($dbtype)->deleteTable($tableName);
        return $re;
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
        $this->connectDb();
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
        $sqlnb = ltrim($sql, '(');
        $this->connectDb();
        $sth = $this->dbh->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $result = $sth->execute($data);
        if(strtolower(substr($sqlnb, 0, 4)) == 'show' || strtolower(substr($sqlnb, 0, 6)) == 'select' || strtolower(substr($sqlnb, 0, 6)) == 'pragma'){
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        }
        elseif(strtolower(substr($sqlnb, 0, 6)) == 'insert'){
            $result = $this->dbh->lastInsertId();
        }
        else{
            $result = $sth->rowCount();
        }
        return $result;
    }
    public function sqlRaw($sql, $data = [])
    {
        $sql = trim($sql);
        $sqlnb = ltrim($sql, '(');
        $this->connectDb(true);
        $sth = $this->dbh->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $result = $sth->execute($data);
        if(strtolower(substr($sqlnb, 0, 4)) == 'show' || strtolower(substr($sqlnb, 0, 6)) == 'select' || strtolower(substr($sqlnb, 0, 6)) == 'pragma'){
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }
    /**
     * 获取表
     * @param  string  $dbName
     * @return array
     */
    public function getTables($dbName)
    {
        $dbtype = $this->app->getDb('type');
        $dbtype = 'jsnpp\db\\' . ucfirst(strtolower(trim($dbtype)));
        $re = $this->app->get($dbtype)->getTables($dbName);
        return $re;
    }
    /**
     * 获取字段
     * @param  string  $tableName
     * @return array
     */
    public function getFields($tableName)
    {
        $dbtype = $this->app->getDb('type');
        $dbtype = 'jsnpp\db\\' . ucfirst(strtolower(trim($dbtype)));
        $re = $this->app->get($dbtype)->getFields($tableName);
        return $re;
    }
    /**
     * 清除表记录
     * @param  string  $tableName
     * @return bool
     */
    public function clearTable($tableName)
    {
        $dbtype = $this->app->getDb('type');
        $dbtype = 'jsnpp\db\\' . ucfirst(strtolower(trim($dbtype)));
        $re = $this->app->get($dbtype)->clearTable($tableName);
        return $re;
    }
}