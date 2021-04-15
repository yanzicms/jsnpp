<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

use jsnpp\exception\DbSyntaxException;
use jsnpp\exception\PDOExecutionException;

class Db extends Connector
{
    protected $database;
    protected $pagination;
    protected $cache;
    private $join = [];
    private $union = [];
    private $slice = [];
    private $beginTransaction = [];
    private $endTransaction = [];
    private $statement = [];
    private $execcode = [];
    private $dbprefix = '';
    private $boxs = [];
    protected function initialize(){
        $this->database = $this->app->get('database');
        $this->pagination = $this->app->get('pagination');
        $this->cache = $this->app->get('cache');
        $this->dbprefix = $this->app->getDb('prefix');
    }
    protected function finalExec()
    {
        $this->execcode = [];
        foreach($this->statement as $item){
            if($item['action'] == 'insert'){
                $isTwoDim = Tools::isTwoDimensionalArray($item['slice']['data']);
                if($isTwoDim){
                    $field = array_keys(current($item['slice']['data']));
                }
                else{
                    $field = array_keys($item['slice']['data']);
                }
                $value = str_repeat('?', count($field));
                $value = trim(chunk_split($value, 1, ','), ',');
                $field = implode(',', $field);
                if($isTwoDim){
                    $varr = [];
                    $statement = 'INSERT INTO ' . $item['slice']['table'] . ' ('.$field.') VALUES ';
                    foreach($item['slice']['data'] as $tkey => $tval){
                        $varr = array_merge($varr, array_values($tval));
                        $statement .= '('.$value.'),';
                    }
                    $statement = rtrim($statement, ',');
                }
                else{
                    $varr = array_values($item['slice']['data']);
                    $statement = 'INSERT INTO ' . $item['slice']['table'] . ' ('.$field.') VALUES ('.$value.')';
                }
                $removeCache = isset($item['slice']['removeCache']) ? $item['slice']['removeCache'] : '';
                $transaction = $this->getTransaction($item['sign']);
                $box = $this->getBox($item['slice']);
                $this->execcode[] = [
                    'sign' => $item['sign'],
                    'action' => $item['action'],
                    'transaction' => $transaction,
                    'statement' => $statement,
                    'bind' => $varr,
                    'removecache' => $removeCache,
                    'box' => $box
                ];
            }
            elseif($item['action'] == 'update'){
                $field = [];
                $varr = [];
                foreach($item['slice']['data'] as $key => $val){
                    if($this->inexpression($key, $val)){
                        $field[] = $key . '=' . $val;
                    }
                    else{
                        $field[] = $key . '= ? ';
                        $varr[] = $val;
                    }
                }
                $set = implode(',', $field);
                $whereParam = isset($item['slice']['whereParam']) ? $item['slice']['whereParam'] : [];
                if(isset($item['slice']['where'])){
                    list($where, $whereArr) = $this->getWhere($item['slice']['where'], $whereParam);
                }
                else{
                    $where = '';
                    $whereArr = [];
                }
                if(count($whereArr) > 0){
                    $varr = array_merge($varr, $whereArr);
                }
                $statement = 'UPDATE '.$item['slice']['table'].' SET '.$set.' WHERE '.$where;
                $removeCache = isset($item['slice']['removeCache']) ? $item['slice']['removeCache'] : '';
                $transaction = $this->getTransaction($item['sign']);
                $box = $this->getBox($item['slice']);
                $this->execcode[] = [
                    'sign' => $item['sign'],
                    'action' => $item['action'],
                    'transaction' => $transaction,
                    'statement' => $statement,
                    'bind' => $varr,
                    'removecache' => $removeCache,
                    'box' => $box
                ];
            }
            elseif($item['action'] == 'delete'){
                $whereParam = isset($item['slice']['whereParam']) ? $item['slice']['whereParam'] : [];
                if(isset($item['slice']['where'])){
                    list($where, $whereArr) = $this->getWhere($item['slice']['where'], $whereParam);
                }
                else{
                    $where = '';
                    $whereArr = [];
                }
                $statement = 'DELETE FROM ' . $item['slice']['table'] . ' WHERE ' . $where;
                $removeCache = isset($item['slice']['removeCache']) ? $item['slice']['removeCache'] : '';
                $transaction = $this->getTransaction($item['sign']);
                $box = $this->getBox($item['slice']);
                $this->execcode[] = [
                    'sign' => $item['sign'],
                    'action' => $item['action'],
                    'transaction' => $transaction,
                    'statement' => $statement,
                    'bind' => $whereArr,
                    'removecache' => $removeCache,
                    'box' => $box
                ];
            }
            elseif($item['action'] == 'select'){
                $whereParam = isset($item['slice']['whereParam']) ? $item['slice']['whereParam'] : [];
                if(isset($item['slice']['where'])){
                    list($where, $whereArr) = $this->getWhere($item['slice']['where'], $whereParam);
                }
                else{
                    $where = '';
                    $whereArr = [];
                }
                if(isset($item['slice']['paging'])){
                    $currentpage = $this->currentpage();
                    $statement = $this->getSelect($item['slice'], $where, false, $item['slice']['paging']['per'], $currentpage);
                }
                else{
                    $statement = $this->getSelect($item['slice'], $where);
                }
                $cache = isset($item['slice']['cache']) ? $item['slice']['cache'] : 0;
                $cacheTag = isset($item['slice']['cacheTag']) ? $item['slice']['cacheTag'] : '';
                $transaction = $this->getTransaction($item['sign']);
                $box = $this->getBox($item['slice']);
                $paging = [];
                $countstatement = '';
                if(isset($item['slice']['paging'])){
                    $paging = $item['slice']['paging'];
                    $countstatement = $this->getploySelect($item['slice'], $where, 'COUNT', 'total', '*');
                }
                $this->execcode[] = [
                    'sign' => $item['sign'],
                    'action' => $item['action'],
                    'transaction' => $transaction,
                    'statement' => $statement,
                    'countstatement' => $countstatement,
                    'bind' => $whereArr,
                    'cache' => $cache,
                    'cachetag' => $cacheTag,
                    'paging' => $paging,
                    'box' => $box
                ];
            }
            elseif($item['action'] == 'subquery'){
                $whereParam = isset($item['slice']['whereParam']) ? $item['slice']['whereParam'] : [];
                if(isset($item['slice']['where'])){
                    list($where, $whereArr) = $this->getWhere($item['slice']['where'], $whereParam);
                }
                else{
                    $where = '';
                    $whereArr = [];
                }
                $statement = $this->getSelect($item['slice'], $where);
                $transaction = $this->getTransaction($item['sign']);
                $box = $this->getBox($item['slice']);
                $this->execcode[] = [
                    'sign' => $item['sign'],
                    'action' => $item['action'],
                    'transaction' => $transaction,
                    'statement' => $statement,
                    'bind' => $whereArr,
                    'box' => $box
                ];
            }
            elseif($item['action'] == 'find'){
                $whereParam = isset($item['slice']['whereParam']) ? $item['slice']['whereParam'] : [];
                if(isset($item['slice']['where'])){
                    list($where, $whereArr) = $this->getWhere($item['slice']['where'], $whereParam);
                }
                else{
                    $where = '';
                    $whereArr = [];
                }
                $statement = $this->getSelect($item['slice'], $where, true);
                $cache = isset($item['slice']['cache']) ? $item['slice']['cache'] : 0;
                $cacheTag = isset($item['slice']['cacheTag']) ? $item['slice']['cacheTag'] : '';
                $transaction = $this->getTransaction($item['sign']);
                $box = $this->getBox($item['slice']);
                $this->execcode[] = [
                    'sign' => $item['sign'],
                    'action' => $item['action'],
                    'transaction' => $transaction,
                    'statement' => $statement,
                    'bind' => $whereArr,
                    'cache' => $cache,
                    'cachetag' => $cacheTag,
                    'box' => $box
                ];
            }
            elseif($item['action'] == 'count'){
                $whereParam = isset($item['slice']['whereParam']) ? $item['slice']['whereParam'] : [];
                if(isset($item['slice']['where'])){
                    list($where, $whereArr) = $this->getWhere($item['slice']['where'], $whereParam);
                }
                else{
                    $where = '';
                    $whereArr = [];
                }
                $count = null;
                if(isset($item['slice']['count'])){
                    $count = $item['slice']['count'];
                }
                $statement = $this->getploySelect($item['slice'], $where, $item['action'], 'count', $count);
                $cache = isset($item['slice']['cache']) ? $item['slice']['cache'] : 0;
                $cacheTag = isset($item['slice']['cacheTag']) ? $item['slice']['cacheTag'] : '';
                $transaction = $this->getTransaction($item['sign']);
                $box = $this->getBox($item['slice']);
                $this->execcode[] = [
                    'sign' => $item['sign'],
                    'action' => $item['action'],
                    'transaction' => $transaction,
                    'statement' => $statement,
                    'bind' => $whereArr,
                    'cache' => $cache,
                    'cachetag' => $cacheTag,
                    'box' => $box
                ];
            }
            elseif($item['action'] == 'max' || $item['action'] == 'min' || $item['action'] == 'avg' || $item['action'] == 'sum'){
                $whereParam = isset($item['slice']['whereParam']) ? $item['slice']['whereParam'] : [];
                if(isset($item['slice']['where'])){
                    list($where, $whereArr) = $this->getWhere($item['slice']['where'], $whereParam);
                }
                else{
                    $where = '';
                    $whereArr = [];
                }
                $statement = $this->getploySelect($item['slice'], $where, $item['action'], $item['action'], $item['slice'][$item['action']]);
                $cache = isset($item['slice']['cache']) ? $item['slice']['cache'] : 0;
                $cacheTag = isset($item['slice']['cacheTag']) ? $item['slice']['cacheTag'] : '';
                $transaction = $this->getTransaction($item['sign']);
                $box = $this->getBox($item['slice']);
                $this->execcode[] = [
                    'sign' => $item['sign'],
                    'action' => $item['action'],
                    'transaction' => $transaction,
                    'statement' => $statement,
                    'bind' => $whereArr,
                    'cache' => $cache,
                    'cachetag' => $cacheTag,
                    'box' => $box
                ];
            }
            elseif($item['action'] == 'join'){
                $whereArrAll = [];
                $fields = '';
                $whereAll = '';
                $jonAll = '';
                $distinct = '';
                $groupAll = '';
                $havingAll = '';
                $orderAll = '';
                $limitAll = '';
                $cacheAll = 0;
                $cacheTagAll = '';
                $pagingAll = [];
                $tmpon = '';
                $tmpname = '';
                $tmptablename = '';
                foreach($item['slice'] as $joinItem){
                    $whereParam = isset($joinItem['slice']['whereParam']) ? $joinItem['slice']['whereParam'] : [];
                    $prefix = isset($joinItem['slice']['alias']) ? $joinItem['slice']['alias'] : $joinItem['slice']['table'];
                    if(isset($joinItem['slice']['where'])){
                        list($where, $whereArr) = $this->getWhere($joinItem['slice']['where'], $whereParam, $prefix);
                    }
                    else{
                        $where = '';
                        $whereArr = [];
                    }
                    $whereArrAll = array_merge($whereArrAll, $whereArr);
                    $selfield = $this->getField($joinItem['slice']['field'], $prefix);
                    $fields .= empty($fields) ? $selfield : ',' . $selfield;
                    if(!empty($where)){
                        $whereAll .= empty($whereAll) ? '(' . $where . ')' : ' AND (' . $where . ')';
                    }
                    $tname = $joinItem['slice']['table'];
                    if(isset($joinItem['slice']['alias'])){
                        $tname .= ' ' . $joinItem['slice']['alias'];
                        $tone = $joinItem['slice']['alias'];
                    }
                    else{
                        $tone = $tname;
                    }
                    if($joinItem['action'] == 'leftjoin'){
                        $jname = ' LEFT JOIN ';
                    }
                    elseif($joinItem['action'] == 'rightjoin'){
                        $jname = ' RIGHT JOIN ';
                    }
                    else{
                        $jname = ' INNER JOIN ';
                    }
                    $jonAll .= empty($tmpon) ? $tname : $tmpname . $tname . ' ON ' . $this->getCondition($tmpon, $tmptablename, $tone);
                    $tmpname = $jname;
                    $tmptablename = $tone;
                    $tmpon = $joinItem['condition'];
                    if(isset($joinItem['slice']['distinct'])){
                        $distinct = 'DISTINCT';
                    }
                    if(isset($joinItem['slice']['group'])){
                        $group = $this->addprefix($joinItem['slice']['group'], $prefix);
                        $groupAll = empty($groupAll) ? $group : ',' . $group;
                    }
                    if(isset($joinItem['slice']['having'])){
                        $having = $this->addprefix($joinItem['slice']['having'], $prefix);
                        $havingAll = empty($havingAll) ? $having : ',' . $having;
                    }
                    if(isset($joinItem['slice']['order'])){
                        $order = $this->addprefix($joinItem['slice']['order'], $prefix);
                        $orderAll = empty($orderAll) ? $order : ',' . $order;
                    }
                    if(isset($joinItem['slice']['limit'])){
                        $limitAll = $joinItem['slice']['limit'];
                    }
                    if(isset($joinItem['slice']['cache']) && $joinItem['slice']['cache'] > $cacheAll){
                        $cacheAll = $joinItem['slice']['cache'];
                    }
                    if(isset($joinItem['slice']['cacheTag'])){
                        $cacheTagAll = $joinItem['slice']['cacheTag'];
                    }
                    if(isset($joinItem['slice']['paging'])){
                        $pagingAll = $joinItem['slice']['paging'];
                    }
                }
                $ispaging = count($pagingAll) > 0 ? true : false;
                $countstatement = '';
                if($ispaging){
                    $countstatement = 'SELECT COUNT(*) AS total FROM ';
                    $countstatement .= $jonAll;
                    if(!empty($whereAll)){
                        $countstatement .= ' WHERE ' . $whereAll;
                    }
                    if(!empty($groupAll)){
                        $countstatement .= ' GROUP BY ' . $groupAll;
                    }
                    if(!empty($havingAll)){
                        $countstatement .= ' HAVING ' . $havingAll;
                    }
                    if(!empty($orderAll)){
                        $countstatement .= ' ORDER BY ' . $orderAll;
                    }
                }
                $statement = 'SELECT ';
                if(!empty($distinct)){
                    $statement .= 'DISTINCT ';
                }
                $statement .= $fields . ' FROM ' . $jonAll;
                if(!empty($whereAll)){
                    $statement .= ' WHERE ' . $whereAll;
                }
                if(!empty($groupAll)){
                    $statement .= ' GROUP BY ' . $groupAll;
                }
                if(!empty($havingAll)){
                    $statement .= ' HAVING ' . $havingAll;
                }
                if(!empty($orderAll)){
                    $statement .= ' ORDER BY ' . $orderAll;
                }
                if($ispaging){
                    $currentpage = $this->currentpage();
                    $ll = $pagingAll['per'] * ($currentpage - 1);
                    $statement .= ' LIMIT ' . $ll . ',' . $pagingAll['per'];
                }
                elseif(!empty($limitAll)){
                    $statement .= ' LIMIT ' . $limitAll;
                }
                $transaction = $this->getTransaction($item['sign']);
                $box = $this->getBox($item['slice']);
                $this->execcode[] = [
                    'sign' => $item['sign'],
                    'action' => $item['action'],
                    'transaction' => $transaction,
                    'statement' => $statement,
                    'countstatement' => $countstatement,
                    'bind' => $whereArrAll,
                    'cache' => $cacheAll,
                    'cachetag' => $cacheTagAll,
                    'paging' => $pagingAll,
                    'box' => $box
                ];
            }
            elseif($item['action'] == 'union'){
                $unionStatement = '';
                $unioncache = 0;
                $unioncacheTag = '';
                $whereArrAll = [];
                foreach($item['slice'] as $unionItem){
                    $whereParam = isset($unionItem['slice']['whereParam']) ? $unionItem['slice']['whereParam'] : [];
                    if(isset($unionItem['slice']['where'])){
                        list($where, $whereArr) = $this->getWhere($unionItem['slice']['where'], $whereParam);
                    }
                    else{
                        $where = '';
                        $whereArr = [];
                    }
                    $whereArrAll = array_merge($whereArrAll, $whereArr);
                    $unionStatement .= $this->getSelect($unionItem['slice'], $where);
                    if($unionItem['action'] == 'union'){
                        $unionStatement .= ' UNION ';
                    }
                    elseif($unionItem['action'] == 'unionall'){
                        $unionStatement .= ' UNION ALL ';
                    }
                    if(isset($unionItem['slice']['cache']) && $unionItem['slice']['cache'] > $unioncache){
                        $unioncache = $unionItem['slice']['cache'];
                    }
                    if(isset($unionItem['slice']['cacheTag'])){
                        $unioncacheTag = $unionItem['slice']['cacheTag'];
                    }
                }
                $transaction = $this->getTransaction($item['sign']);
                $box = $this->getBox($item['slice']);
                $this->execcode[] = [
                    'sign' => $item['sign'],
                    'action' => $item['action'],
                    'transaction' => $transaction,
                    'statement' => $unionStatement,
                    'bind' => $whereArrAll,
                    'cache' => $unioncache,
                    'cachetag' => $unioncacheTag,
                    'box' => $box
                ];
            }
        }
        $hasTransaction = (count($this->beginTransaction) > 0) ? true : false;
        $this->beginTransaction = [];
        $this->endTransaction = [];
        $this->statement = [];
        $this->boxs = array_merge($this->boxs, $this->box->get());
        try{
            foreach($this->execcode as $dbexec){
                if($dbexec['transaction'] == 'beginTransaction'){
                    $this->database->beginTransaction();
                }
                if($dbexec['action'] == 'subquery'){
                    $this->boxs[$dbexec['box']] = [
                        'statement' => $dbexec['statement'],
                        'bind' => $dbexec['bind'],
                        '_subquery' => 'subquery'
                    ];
                    continue;
                }
                $statement = $dbexec['statement'];
                $this->dobind($dbexec['bind']);
                $statementbind = $dbexec['bind'];
                list($statement, $statementbind) = $this->dealstatement($statement, $statementbind);
                if(!preg_match('/\s+in\s+\(\)/i', preg_replace('/(".*?")|(\'.*?\')/', '', $statement))){
                    $hascache = false;
                    $symbol = '';
                    $result = '';
                    if(isset($dbexec['cache'])){
                        $symbol = md5(serialize([$statement, $statementbind]) . (isset($_GET['page']) ? '_' . $_GET['page'] : ''));
                        if($this->cache->has($symbol)){
                            $result = $this->cache->get($symbol);
                            $hascache = true;
                        }
                    }
                    if($hascache == false){
                        $result = $this->database->sql($statement, $statementbind);
                        if(isset($dbexec['paging']) && !empty($dbexec['paging'])){
                            $total = $dbexec['paging']['total'];
                            if($total < 0 && isset($dbexec['countstatement'])){
                                $countstatement = $dbexec['countstatement'];
                                $countstatementbind = $dbexec['bind'];
                                list($countstatement, $countstatementbind) = $this->dealstatement($countstatement, $countstatementbind);
                                $recount = $this->database->sql($countstatement, $countstatementbind);
                                $total = $recount[0]['total'];
                            }
                            $page = $this->currentpage();
                            $pages = ceil($total / $dbexec['paging']['per']);
                            $paramArr = $dbexec['paging']['param'];
                            if(isset($paramArr['page'])){
                                unset($paramArr['page']);
                            }
                            parse_str($_SERVER['QUERY_STRING'], $queryArr);
                            if(isset($queryArr['page'])){
                                unset($queryArr['page']);
                            }
                            $queryArr = array_merge($queryArr, $paramArr);
                            $paramStr = http_build_query($queryArr);
                            $urlPath = parse_url($this->request->requestUri(), PHP_URL_PATH);
                            $pagingUrl = empty($paramStr) ? $urlPath . '?page=' : $urlPath . '?' . $paramStr . '&page=';
                            $result = [
                                'total' => $total,
                                'per' => $dbexec['paging']['per'],
                                'page' => $page,
                                'pages' => $pages,
                                'paging' => $this->pagination->getHtml($page, $pages, $pagingUrl),
                                'simplePaging' => $this->pagination->getSimpleHtml($page, $pages, $pagingUrl),
                                'data' => $result,
                            ];
                        }
                        else{
                            if(in_array($dbexec['action'], ['count','max','min','avg','sum'])){
                                $result = $result[0][$dbexec['action']];
                            }
                            elseif($dbexec['action'] == 'find'){
                                if(isset($result[0])){
                                    $result = $result[0];
                                }
                                else{
                                    $result = [];
                                }
                            }
                        }
                    }
                    if(isset($dbexec['cache']) && !empty($dbexec['cachetag'])){
                        $this->cache->tag($dbexec['cachetag'])->set($symbol, $result, intval($dbexec['cache']));
                    }
                    elseif(isset($dbexec['cache'])){
                        $this->cache->set($symbol, $result, intval($dbexec['cache']));
                    }
                    if(!empty($dbexec['removecache'])){
                        $this->cache->deleteTag($dbexec['removecache']);
                    }
                    if(isset($dbexec['box']) && !empty($dbexec['box'])){
                        $this->boxs[$dbexec['box']] = $this->box->set($dbexec['box'], $result);
                    }
                }
                if($dbexec['transaction'] == 'endTransaction'){
                    $this->database->endTransaction();
                }
            }
            $this->boxs['transactionIsOk'] = $this->box->set('transactionIsOk', true);
        }
        catch (\PDOException $e){
            if($hasTransaction){
                $this->database->rollBack();
                $this->boxs['transactionIsOk'] = $this->box->set('transactionIsOk', false);
            }
            throw new PDOExecutionException('Database execution error: ' . $e->getMessage());
        }
    }
    private function dobind(&$bind)
    {
        foreach($bind as $key => $val){
            if(preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $val, $metchs)){
                $value = $this->findValue($metchs[1]);
                $bind[$key] = $value;
            }
        }
    }
    private function findValue($value)
    {
        $commandArr = explode('.', $value);
        $value = array_shift($commandArr);
        $value = $this->boxs[$value];
        if(count($commandArr) > 0){
            foreach($commandArr as $cval){
                if(isset($value[$cval])){
                    $value = $value[$cval];
                }
                else{
                    $value = null;
                    break;
                }
            }
        }
        return $value;
    }
    private function currentpage()
    {
        return isset($_GET['page']) ? $_GET['page'] : 1;
    }
    private function dealstatement($statement, $statementbind)
    {
        while($this->hasBoxStr($statement)){
            preg_match('/:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)/i', $statement, $metches);
            if(is_array($this->boxs[$metches[1]]) && isset($this->boxs[$metches[1]]['_subquery'])){
                list($statement, $statementbind) = $this->dostatement($metches, $statement, $statementbind);
            }
            else{
                $value = $this->findValue($metches[1]);
                $statement = preg_replace('/(\s+in\s+)' . str_replace(['(', ')'], ['\(', '\)'], $metches[0]) . '/i', '$1(' . $value . ')', $statement);
                $statement = str_replace($metches[0], '\'' . $value . '\'', $statement);
            }
        }
        return [$statement, $statementbind];
    }
    private function dostatement($metches, $statement, $bind)
    {
        $statementArr = explode($metches[0], $statement);
        $bingArr = [];
        $sarrlen = count($statementArr) -1;
        $start = 0;
        foreach($statementArr as $skey => $sval){
            if($skey < $sarrlen){
                $sval = preg_replace(['/(?<!\\\\)".*?(?<!\\\\)"/', "/(?<!\\\\)'.*?(?<!\\\\)'/"], '', $sval);
                $qnum = substr_count($sval, '?');
                if($qnum > 0){
                    $bingArr = array_merge($bingArr, array_slice($bind, $start, $qnum), $this->boxs[$metches[1]]['bind']);
                    $start += $qnum;
                }
                else{
                    $bingArr = array_merge($bingArr, $this->boxs[$metches[1]]['bind']);
                }
            }
            else{
                $bingArr = array_merge($bingArr, array_slice($bind, $start));
            }
        }
        $statement = str_replace($metches[0], '(' . $this->boxs[$metches[1]]['statement'] . ')', $statement);
        return [$statement, $bingArr];
    }
    private function getBox($slice)
    {
        $box = '';
        if(isset($slice['box'])){
            $box = trim($slice['box']);
        }
        else{
            foreach($slice as $key => $val){
                if(isset($val['slice']) && isset($val['slice']['box'])){
                    $box = trim($val['slice']['box']);
                }
            }
        }
        return $box;
    }
    private function getTransaction($sign)
    {
        $transaction = '';
        if(count($this->beginTransaction) > 0){
            if(in_array($sign, $this->beginTransaction)){
                $transaction = 'beginTransaction';
            }
            elseif(in_array($sign, $this->endTransaction)){
                $transaction = 'endTransaction';
            }
        }
        return $transaction;
    }
    private function getCondition($tmpon, $prevname, $nextname)
    {
        $tmparr = explode('=', $tmpon);
        if(strpos($tmparr[0], '.') === false){
            $tmparr[0] = $prevname . '.' . ltrim($tmparr[0]);
        }
        if(strpos($tmparr[1], '.') === false){
            $tmparr[1] = ' ' . $nextname . '.' . ltrim($tmparr[1]);
        }
        return implode('=', $tmparr);
    }
    private function getField($field, $prefix)
    {
        return $this->addprefix($field, $prefix);
    }
    private function getploySelect($slice, $where, $ploy, $alias, $field = null)
    {
        $statement = 'SELECT ';
        $statement .= strtoupper($ploy) . '(';
        if(isset($slice['distinct']) && isset($slice['field']) && is_null($field)){
            $statement .= 'DISTINCT ';
        }
        $statement .= is_null($field) ? (isset($slice['field']) ? $slice['field'] : '*') : $field;
        $statement .= ') AS ' . $alias;
        $statement .= ' FROM ' . $slice['table'];
        if(isset($slice['alias'])){
            $statement .= ' ' . $slice['alias'];
        }
        if(!empty($where)){
            $statement .= ' WHERE ' . $where;
        }
        if(isset($slice['group'])){
            $statement .= ' GROUP BY ' . $slice['group'];
        }
        if(isset($slice['having'])){
            $statement .= ' HAVING ' . $slice['having'];
        }
        if(isset($slice['order'])){
            $statement .= ' ORDER BY ' . $slice['order'];
        }
        return $statement;
    }
    private function getSelect($slice, $where, $find = false, $per = null, $currentpage = null)
    {
        $statement = 'SELECT ';
        if(isset($slice['distinct'])){
            $statement .= 'DISTINCT ';
        }
        $statement .= isset($slice['field']) ? $slice['field'] : '*';
        $statement .= ' FROM ' . $slice['table'];
        if(isset($slice['alias'])){
            $statement .= ' ' . $slice['alias'];
        }
        if(!empty($where)){
            $statement .= ' WHERE ' . $where;
        }
        if(isset($slice['group'])){
            $statement .= ' GROUP BY ' . $slice['group'];
        }
        if(isset($slice['having'])){
            $statement .= ' HAVING ' . $slice['having'];
        }
        if(isset($slice['order'])){
            $statement .= ' ORDER BY ' . $slice['order'];
        }
        if($find){
            $statement .= ' LIMIT 1';
        }
        elseif(!is_null($per) && !is_null($currentpage)){
            $ll = $per * ($currentpage - 1);
            $statement .= ' LIMIT ' . $ll . ',' . $per;
        }
        elseif(isset($slice['limit'])){
            $statement .= ' LIMIT ' . $slice['limit'];
        }
        return $statement;
    }
    private function getWhere($where, $whereParam = [], $prefix = '')
    {
        $whereCode = '';
        $right = 0;
        foreach($where as $wval){
            $wval[0] = strtolower($wval[0]);
            if(!empty($prefix)){
                $wval[1] = $this->addprefix($wval[1], $prefix);
            }
            if(stripos($wval[1], 'BETWEEN') !== false && stripos($wval[1], 'AND') !== false){
                $wval[1] = '('.$wval[1].')';
            }
            if($wval[0] == 'where'){
                $whereCode .= empty($whereCode) ? $wval[1] : ' AND ' . $wval[1];
            }
            elseif($wval[0] == 'orwhere'){
                $whereCode .= empty($whereCode) ? $wval[1] : ' OR ' . $wval[1];
            }
            elseif($wval[0] == 'andwhere'){
                if(empty($whereCode)){
                    $whereCode = '('.$wval[1];
                    $right ++;
                }
                elseif($right > 0){
                    $whereCode .= ') AND ('.$wval[1];
                    $right --;
                }
                else{
                    $whereCode = '('.$whereCode.') AND ('.$wval[1];
                    $right ++;
                }
            }
            elseif($wval[0] == 'whereor'){
                $whereCode .= empty($whereCode) ? '('.$wval[1] : ' OR (' . $wval[1];
                $right ++;
            }
            elseif($wval[0] == 'whereand'){
                $whereCode .= empty($whereCode) ? '('.$wval[1] : ' AND (' . $wval[1];
                $right ++;
            }
        }
        if($right > 0){
            $whereCode .= str_repeat(')', $right);
        }
        $whereArr = [];
        if(count($whereParam) > 0){
            foreach($whereParam as $pval){
                $whereArr = array_merge($whereArr, $pval);
            }
        }
        return [$whereCode, $whereArr];
    }
    public function table($name)
    {
        $this->set('execTable', $name);
        return $this;
    }
    protected function execTable($name)
    {
        $name = trim($name);
        $this->slice['table'] = substr($name, 0, 5) == ':box(' ? $name : $this->dbprefix . $name;
        return $this->reok();
    }
    public function field($string)
    {
        $this->set('execField', $string);
        return $this;
    }
    protected function execField($string)
    {
        $this->slice['field'] = trim($string);
        return $this->reok();
    }
    public function order($string)
    {
        $this->set('execOrder', $string);
        return $this;
    }
    protected function execOrder($string)
    {
        $this->slice['order'] = trim($string);
        return $this->reok();
    }
    public function limit($offset, $rows = null)
    {
        $this->set('execLimit', $offset, $rows);
        return $this;
    }
    protected function execLimit($offset, $rows)
    {
        $string = strval(intval(trim($offset)));
        if(!is_null($rows)){
            $string .= ',' . strval(intval(trim($rows)));
        }
        $this->slice['limit'] = $string;
        return $this->reok();
    }
    public function group($string)
    {
        $this->set('execGroup', $string);
        return $this;
    }
    protected function execGroup($string)
    {
        $this->slice['group'] = trim($string);
        return $this->reok();
    }
    public function having($string)
    {
        $this->set('execHaving', $string);
        return $this;
    }
    protected function execHaving($string)
    {
        $this->slice['having'] = trim($string);
        return $this->reok();
    }
    public function distinct()
    {
        $this->set('execDistinct');
        return $this;
    }
    protected function execDistinct()
    {
        $this->slice['distinct'] = 'DISTINCT';
        return $this->reok();
    }
    public function alias($alias)
    {
        $this->set('execAlias', $alias);
        return $this;
    }
    protected function execAlias($alias)
    {
        $this->slice['alias'] = $alias;
        return $this->reok();
    }
    public function cache($time, $tag = '')
    {
        $this->set('execCache', $time, $tag);
        return $this;
    }
    protected function execCache($time, $tag)
    {
        $this->slice['cache'] = $time;
        if(!empty($tag)){
            $this->slice['cacheTag'] = $tag;
        }
        return $this->reok();
    }
    public function removeCache($tag = '')
    {
        $this->set('execRemoveCache', $tag);
        return $this;
    }
    protected function execRemoveCache($tag)
    {
        $this->slice['removeCache'] = $tag;
        return $this->reok();
    }
    public function box($name)
    {
        $this->set('execBox', $name);
        return $this;
    }
    protected function execBox($name)
    {
        $this->slice['box'] = $name;
        return $this->reok();
    }
    public function paging($per, $param = [], $total = -1)
    {
        $this->set('execPaging', $per, $param, $total);
        return $this;
    }
    protected function execPaging($per, $param, $total)
    {
        if(is_null($total) && !is_array($param) && ctype_digit(strval($param))){
            $total = $param;
            $param = [];
        }
        $this->slice['paging'] = [
            'per' => intval($per),
            'param' => $param,
            'total' => $total,
        ];
        return $this->reok();
    }
    public function where($statement, $judgment = '', $condition = null)
    {
        $this->set('execWhere', $statement, $judgment, $condition);
        return $this;
    }
    protected function execWhere($statement, $judgment, $condition)
    {
        $this->dowhere($statement, $judgment, $condition, 'where');
        return $this->reok();
    }
    public function orWhere($statement, $judgment = '', $condition = null)
    {
        $this->set('execOrWhere', $statement, $judgment, $condition);
        return $this;
    }
    protected function execOrWhere($statement, $judgment, $condition)
    {
        $this->dowhere($statement, $judgment, $condition, 'orwhere');
        return $this->reok();
    }
    public function andWhere($statement, $judgment = '', $condition = null)
    {
        $this->set('execAndWhere', $statement, $judgment, $condition);
        return $this;
    }
    protected function execAndWhere($statement, $judgment, $condition)
    {
        $this->dowhere($statement, $judgment, $condition, 'andwhere');
        return $this->reok();
    }
    public function whereOr($statement, $judgment = '', $condition = null)
    {
        $this->set('execWhereOr', $statement, $judgment, $condition);
        return $this;
    }
    protected function execWhereOr($statement, $judgment, $condition)
    {
        $this->dowhere($statement, $judgment, $condition, 'whereor');
        return $this->reok();
    }
    public function whereAnd($statement, $judgment = '', $condition = null)
    {
        $this->set('execWhereAnd', $statement, $judgment, $condition);
        return $this;
    }
    protected function execWhereAnd($statement, $judgment, $condition)
    {
        $this->dowhere($statement, $judgment, $condition, 'whereand');
        return $this->reok();
    }
    private function dowhere($statement, $judgment, $condition, $name)
    {
        $ispre = Tools::hasString($statement, ['?']);
        $single = Tools::hasString($statement, ['=', '!=', '>', '<', '>=', '<=', ' BETWEEN ', ' LIKE ', ' IN ']);
        if($ispre && (empty($judgment) || $condition != '')){
            throw new DbSyntaxException('Database syntax error: Prepared statement needs and only needs another parameter');
        }
        elseif(!$ispre && $single && ($judgment != '' || $condition != '')){
            throw new DbSyntaxException('Database syntax error: Conditional statement can only use one parameter');
        }
        if(!$ispre && $single){
            $this->slice['where'][] = [$name, $statement];
        }
        elseif($ispre){
            $numqus = substr_count($statement, '?');
            if(is_string($judgment)){
                $judgment = Tools::toArrTrim($judgment, ',');
            }
            elseif(!is_array($judgment)){
                $judgment = [$judgment];
            }
            if($numqus != count($judgment)){
                throw new DbSyntaxException('Database syntax error: The parameters of the prepared statement do not match');
            }
            if(false !== $isbox = $this->isBoxStr($judgment)){
                $starr = explode('?', $statement);
                $statstr = '';
                $starrlen = count($starr) - 1;
                for($i = 0; $i < $starrlen; $i ++){
                    if(in_array($i, $isbox)){
                        $statstr .= $starr[$i] . $judgment[$i];
                        unset($judgment[$i]);
                    }
                    else{
                        $statstr .= $starr[$i] . '?';
                    }
                }
                $statstr .= end($starr);
                $this->slice['where'][] = [$name, $statstr];
                if(count($judgment) > 0){
                    $this->slice['whereParam'][] = $judgment;
                }
            }
            else{
                $this->slice['where'][] = [$name, $statement];
                $this->slice['whereParam'][] = $judgment;
            }
        }
        else{
            if(is_null($condition)){
                $condition = $judgment;
                $judgment = '=';
            }
            $judgment = strtoupper(Tools::oneSpace($judgment));
            if($judgment == 'BETWEEN' || $judgment == 'NOT BETWEEN'){
                if((is_array($condition) && count($condition) != 2) || (is_string($condition) && (strpos($condition, ',') === false || substr_count($condition, ',') != 1))){
                    throw new DbSyntaxException('Database syntax error: BETWEEN requires two parameters');
                }
                if(is_string($condition)){
                    $condition = Tools::toArrTrim($condition, ',');
                }
                elseif(!is_array($condition)){
                    $condition = [$condition];
                }
                $this->slice['where'][] = [$name, $statement . ' ' . $judgment . ' ? AND ?'];
                $this->slice['whereParam'][] = $condition;
            }
            elseif($judgment == 'IN' || $judgment == 'NOT IN'){
                if(false !== $isbox = $this->isBoxStr($condition)){
                    if(is_array($condition)){
                        $qstr = '';
                        $incondit = [];
                        foreach($condition as $key => $val){
                            if(in_array($key, $isbox)){
                                $qstr .= empty($qstr) ? $val : ',' . $val;
                            }
                            else{
                                $qstr .= empty($qstr) ? '?' : ',?';
                                $incondit[] = $val;
                            }
                        }
                        $this->slice['where'][] = [$name, $statement . ' ' . $judgment . ' (' . $qstr . ')'];
                        if(count($incondit) > 0){
                            $this->slice['whereParam'][] = $incondit;
                        }
                    }
                    else{
                        $this->slice['where'][] = [$name, $statement . ' ' . $judgment . ' ' . $condition];
                    }
                }
                else{
                    if(!is_array($condition)){
                        $condition = Tools::toArrTrim($condition, ',');
                    }
                    $qstr = str_repeat('?', count($condition));
                    $qstr = trim(chunk_split($qstr, 1, ','), ',');
                    $this->slice['where'][] = [$name, $statement . ' ' . $judgment . ' (' . $qstr . ')'];
                    $this->slice['whereParam'][] = $condition;
                }
            }
            else{
                
                $this->slice['where'][] = [$name, $statement . ' ' . $judgment . ' ?'];
                $this->slice['whereParam'][] = [$condition];
            }
        }
    }
    private function isBoxStr($str)
    {
        $re = false;
        if(is_array($str)){
            $keys = [];
            foreach($str as $key => $val){
                $val = trim($val);
                if(preg_match('/^:box *\( *[A-Za-z][A-Za-z0-9_]* *\)$/i', $val)){
                    $keys[] = $key;
                }
            }
            if(count($keys) > 0){
                $re = $keys;
            }
        }
        else{
            $str = trim($str);
            if(preg_match('/^:box *\( *[A-Za-z][A-Za-z0-9_]* *\)$/i', $str)){
                $re = true;
            }
        }
        return $re;
    }
    private function hasBoxStr($string)
    {
        return preg_match('/:box *\( *[A-Za-z][A-Za-z0-9_\.]* *\)/i', $string);
    }
    private function reok()
    {
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    public function select()
    {
        $this->set('execSelect');
        return $this;
    }
    protected function execSelect()
    {
        $this->doAction('select');
        return $this->reok();
    }
    public function subquery()
    {
        $this->set('execSubquery');
        return $this;
    }
    protected function execSubquery()
    {
        $this->doAction('subquery');
        return $this->reok();
    }
    public function count($field = null)
    {
        $this->set('execCount', $field);
        return $this;
    }
    protected function execCount($field)
    {
        if(!is_null($field)){
            $this->slice['count'] = $field;
        }
        $this->doAction('count');
        return $this->reok();
    }
    public function max($field)
    {
        $this->set('execMax', $field);
        return $this;
    }
    protected function execMax($field)
    {
        $this->slice['max'] = $field;
        $this->doAction('max');
        return $this->reok();
    }
    public function min($field)
    {
        $this->set('execMin', $field);
        return $this;
    }
    protected function execMin($field)
    {
        $this->slice['min'] = $field;
        $this->doAction('min');
        return $this->reok();
    }
    public function avg($field)
    {
        $this->set('execAvg', $field);
        return $this;
    }
    protected function execAvg($field)
    {
        $this->slice['avg'] = $field;
        $this->doAction('avg');
        return $this->reok();
    }
    public function sum($field)
    {
        $this->set('execSum', $field);
        return $this;
    }
    protected function execSum($field)
    {
        $this->slice['sum'] = $field;
        $this->doAction('sum');
        return $this->reok();
    }
    public function find()
    {
        $this->set('execFind');
        return $this;
    }
    protected function execFind()
    {
        $this->doAction('find');
        return $this->reok();
    }
    public function delete()
    {
        $this->set('execDelete');
        return $this;
    }
    protected function execDelete()
    {
        $this->doAction('delete');
        return $this->reok();
    }
    public function update($data = null)
    {
        $this->set('execUpdate', $data);
        return $this;
    }
    protected function execUpdate($data)
    {
        if(!isset($this->slice['data']) && (is_null($data) || !is_array($data) || count($data) == 0)){
            throw new DbSyntaxException('Database syntax error: UPDATE statement is missing data');
        }
        if(!isset($this->slice['data'])){
            $this->slice['data'] = $data;
        }
        elseif(is_array($data)){
            $this->slice['data'] = array_merge($this->slice['data'], $data);
        }
        $this->doAction('update');
        return $this->reok();
    }
    public function data($data, $val = null)
    {
        $this->set('execData', $data, $val);
        return $this;
    }
    protected function execData($data, $val)
    {
        if(is_null($val) && !is_array($data)){
            throw new DbSyntaxException('Database syntax error: The parameter of the Data statement is missing');
        }
        if(is_string($data)){
            $data = [$data => $val];
        }
        if(!isset($this->slice['data'])){
            $this->slice['data'] = $data;
        }
        else{
            $this->slice['data'] = array_merge($this->slice['data'], $data);
        }
        return $this->reok();
    }
    public function insert($data = null)
    {
        $this->set('execInsert', $data);
        return $this;
    }
    protected function execInsert($data)
    {
        if(!isset($this->slice['data']) && (is_null($data) || !is_array($data) || count($data) == 0)){
            throw new DbSyntaxException('Database syntax error: INSERT statement is missing data');
        }
        if(!isset($this->slice['data'])){
            $this->slice['data'] = $data;
        }
        elseif(is_array($data)){
            $this->slice['data'] = array_merge($this->slice['data'], $data);
        }
        $this->doAction('insert');
        return $this->reok();
    }
    private function doAction($action)
    {
        $this->statement[] = [
            'sign' => 'sign' . $this->getSign(),
            'action' => $action,
            'slice' => $this->slice
        ];
        $this->addSign();
        $this->slice = [];
    }
    public function join($condition)
    {
        $this->set('execJoin', $condition);
        return $this;
    }
    protected function execJoin($condition)
    {
        $this->doJoin('join', $condition);
        return $this->reok();
    }
    public function leftJoin($condition)
    {
        $this->set('execLeftJoin', $condition);
        return $this;
    }
    protected function execLeftJoin($condition)
    {
        $this->doJoin('leftjoin', $condition);
        return $this->reok();
    }
    public function rightJoin($condition)
    {
        $this->set('execRightJoin', $condition);
        return $this;
    }
    protected function execRightJoin($condition)
    {
        $this->doJoin('rightjoin', $condition);
        return $this->reok();
    }
    private function doJoin($action, $condition = '')
    {
        if(count($this->slice) > 0){
            $this->join[] = [
                'action' => $action,
                'condition' => $condition,
                'slice' => $this->slice
            ];
        }
        $this->slice = [];
    }
    public function endJoin()
    {
        $this->set('execEndJoin');
        return $this;
    }
    protected function execEndJoin()
    {
        $this->doJoin('endjoin');
        $this->doEndJoin('join');
        return $this->reok();
    }
    private function doEndJoin($action)
    {
        $this->statement[] = [
            'sign' => 'sign' . $this->getSign(),
            'action' => $action,
            'slice' => $this->join
        ];
        $this->addSign();
        $this->join = [];
    }
    public function union()
    {
        $this->set('execUnion');
        return $this;
    }
    protected function execUnion()
    {
        $this->doUnion('union');
        return $this->reok();
    }
    public function unionAll()
    {
        $this->set('execUnionAll');
        return $this;
    }
    protected function execUnionAll()
    {
        $this->doUnion('unionall');
        return $this->reok();
    }
    private function doUnion($action)
    {
        if(count($this->slice) > 0){
            $this->union[] = [
                'action' => $action,
                'slice' => $this->slice
            ];
        }
        $this->slice = [];
    }
    public function endUnion()
    {
        $this->set('execEndUnion');
        return $this;
    }
    protected function execEndUnion()
    {
        $this->doUnion('endunion');
        $this->doEndUnion('union');
        return $this->reok();
    }
    private function doEndUnion($action)
    {
        $this->statement[] = [
            'sign' => 'sign' . $this->getSign(),
            'action' => $action,
            'slice' => $this->union
        ];
        $this->addSign();
        $this->union = [];
    }
    public function beginTransaction()
    {
        $this->set('execBeginTransaction');
        return $this;
    }
    protected function execBeginTransaction()
    {
        $this->beginTransaction[] = 'sign' . $this->getSign();
        return $this->reok();
    }
    public function endTransaction()
    {
        $this->set('execEndTransaction');
        return $this;
    }
    protected function execEndTransaction()
    {
        $this->endTransaction[] = 'sign' . ($this->getSign() - 1);
        return $this->reok();
    }
    private function addprefix($str, $prefix)
    {
        if(strpos($str, "'") !== false || strpos($str, '"') !== false){
            if(strpos($str, "'") !== false){
                $arr = preg_split("/(?<!\\\\)'/", $str);
                $arrlen = count($arr);
                for($i = 0; $i < $arrlen; $i += 2){
                    $arr[$i] = $this->doAddprefix($arr[$i], $prefix);
                }
                $str = implode("'", $arr);
            }
            if(strpos($str, '"') !== false){
                $arr = preg_split('/(?<!\\\\)"/', $str);
                $arrlen = count($arr);
                for($i = 0; $i < $arrlen; $i += 2){
                    $arr[$i] = $this->doAddprefix($arr[$i], $prefix);
                }
                $str = implode('"', $arr);
            }
        }
        else{
            $str = $this->doAddprefix($str, $prefix);
        }
        return $str;
    }
    private function doAddprefix($str, $prefix)
    {
        $arr = explode(' ', $str);
        $isas = false;
        foreach($arr as $key => $val){
            if(strtoupper($val) == 'AS'){
                $isas = true;
            }
            if(!empty($val) && !in_array(strtoupper($val), ['AND', 'OR', 'BETWEEN', 'IN', 'NOT', 'LIKE', 'ASC', 'DESC', 'COUNT', 'MAX', 'MIN', 'AVG', 'SUM', 'EXISTS', 'AS'])){
                $val = preg_replace('/([A-Za-z]([A-Za-z0-9_]*[A-Za-z0-9])*)$/i', $prefix . '.$1', $val);
                $val = preg_replace('/([A-Za-z]([A-Za-z0-9_]*[A-Za-z0-9])*)([^A-Za-z0-9_\(\.])/i', $prefix . '.$1$3', $val);
                $prefixdotlen = strlen($prefix) + 1;
                if($isas){
                    $val = substr($val, $prefixdotlen);
                    $isas = false;
                }
                if(strtolower(substr($val, 0, $prefixdotlen + 3)) == $prefix . '.asc' && !preg_match('/\w/i', substr($val, $prefixdotlen + 3, 1))){
                    $val = substr($val, $prefixdotlen);
                }
                if(strtolower(substr($val, 0, $prefixdotlen + 4)) == $prefix . '.desc' && !preg_match('/\w/i', substr($val, $prefixdotlen + 4, 1))){
                    $val = substr($val, $prefixdotlen);
                }
                $arr[$key] = $val;
            }
        }
        $str = implode(' ', $arr);
        return $str;
    }
    private function inexpression($key, $val)
    {
        if(preg_match('/\W' . $key . '\W/', ' ' . $val . ' ')){
            $val = preg_replace('/^\w+\((.*)\)$/', '$1', trim($val));
            $val = preg_replace(['/' . $key . '/', '/\s+/'], '', trim($val));
            $val = str_replace(['+', '-', '*', '/', '%'], '', $val);
            if(preg_match('/^\d+$/', $val)){
                return true;
            }
        }
        return false;
    }
}