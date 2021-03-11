<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Check extends Connector
{
    private $sessions;
    private $cookies;
    private $runresult = null;
    private $param = [];
    public function initialize(){
        $this->sessions = $this->app->get('session');
        $this->cookies = $this->app->get('cookie');
    }
    public function stop($variable, $symbol, $expression = null, $alert = null)
    {
        $this->set('execStop', $variable, $symbol, $expression, $alert);
        return $this;
    }
    protected function execStop($variable, $symbol, $expression, $alert)
    {
        if(is_null($alert) && !in_array(trim($symbol), ['=', '!=', '>', '<', '>=', '<='])){
            $alert = $expression;
            $expression = $symbol;
            $symbol = '=';
        }
        if(preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $variable, $metchs)){
            $variable = $this->findBoxValue($metchs[1]);
        }
        if(preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $expression, $metchs)){
            $expression = $this->findBoxValue($metchs[1]);
        }
        if(preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $alert, $metchs)){
            $alert = $this->findBoxValue($metchs[1]);
        }
        $list = [];
        $message = 'ok';
        $result = $this->judgment($variable, $symbol, $expression);
        if($result){
            $this->ignore();
            if(is_null($alert)){
                return false;
            }
            else{
                $result = false;
                $list[] = $message = $alert;
            }
        }
        else{
            $result = true;
        }
        return [
            'result' => $result,
            'code' => 0,
            'message' => $message,
            'list' => $list
        ];
    }
    private function findBoxValue($mstr)
    {
        $commandArr = explode('.', $mstr);
        $value = array_shift($commandArr);
        $value = $this->box->get($value);
        if(count($commandArr) > 0){
            foreach($commandArr as $val){
                if(isset($value[$val])){
                    $value = $value[$val];
                }
                else{
                    $value = null;
                    break;
                }
            }
        }
        return $value;
    }
    public function session($name, $value, $condition = true)
    {
        $this->set('execSession', $name, $value, $condition);
        return $this;
    }
    protected function execSession($name, $value, $condition)
    {
        if($condition){
            if(preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $value, $metchs)){
                $value = $this->findBoxValue($metchs[1]);
            }
            $this->sessions->set($name, $value);
        }
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    public function cookie($name, $value, $expire, $condition = true)
    {
        $this->set('execCookie', $name, $value, $expire, $condition);
        return $this;
    }
    protected function execCookie($name, $value, $expire, $condition)
    {
        if($condition){
            if(preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $value, $metchs)){
                $value = $this->findBoxValue($metchs[1]);
            }
            $this->cookies->set($name, $value, $expire);
        }
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    public function run($func, $variable = null, $symbol = null, $expression = null)
    {
        $detr = debug_backtrace();
        $class = str_replace('/', '\\', $detr[1]['class']);
        $this->set('execRun', $class, $func, $variable, $symbol, $expression);
        return $this;
    }
    protected function execRun($class, $func, $variable, $symbol, $expression)
    {
        if(is_null($variable) && is_null($symbol) && is_null($expression)){
            $result = true;
        }
        else{
            if(is_null($expression)){
                $expression = $symbol;
                $symbol = '=';
            }
            if(preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $variable, $metchs)){
                $variable = $this->findBoxValue($metchs[1]);
            }
            if(preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $expression, $metchs)){
                $expression = $this->findBoxValue($metchs[1]);
            }
            $result = $this->judgment($variable, $symbol, $expression);
        }
        if($result){
            $this->runresult = $this->app->appMethodRaw($class, $func, $this->param);
        }
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    public function box($name)
    {
        $this->set('execBox', $name);
        return $this;
    }
    protected function execBox($name)
    {
        if(!is_null($this->runresult)){
            $this->box->set(trim($name), $this->runresult);
            $this->runresult = null;
        }
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    public function param(...$param)
    {
        foreach($param as $key => $val){
            if(is_string($val) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $val, $metchs)){
                $param[$key] = $this->findBoxValue($metchs[1]);
            }
        }
        $this->set('execParam', $param);
        return $this;
    }
    protected function execParam($param)
    {
        $this->param = $param;
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    private function judgment($variable, $symbol, $expression)
    {
        $result = false;
        $symbol = trim($symbol);
        switch($symbol){
            case '=':
            case '==':
                if(trim($expression) == 'empty'){
                    $result = empty($variable) ? true : false;
                }
                else{
                    $result = ($variable == $expression) ? true : false;
                }
                break;
            case '!=':
                if(trim($expression) == 'empty'){
                    $result = empty($variable) ? false : true;
                }
                else{
                    $result = ($variable != $expression) ? true : false;
                }
                break;
            case '>':
                $result = ($variable > $expression) ? true : false;
                break;
            case '<':
                $result = ($variable < $expression) ? true : false;
                break;
            case '>=':
                $result = ($variable >= $expression) ? true : false;
                break;
            case '<=':
                $result = ($variable <= $expression) ? true : false;
                break;
        }
        return $result;
    }
}