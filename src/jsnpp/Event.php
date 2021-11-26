<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

/**
 * @property Check check
 * @property Config config
 * @property Db db
 * @property Img img
 * @property Output output
 * @property Upload upload
 */
class Event extends Connector
{
    private $handle;
    public function initialize(){
        $this->handle = $this->app->get('handle');
    }
    /**
     * @return Event
     */
    public function register($event)
    {
        $this->set('execRegister', $event);
        return $this;
    }
    protected function execRegister($event)
    {
        $this->handle->register($event);
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    /**
     * @return Event
     */
    public function listen($method, $param = [])
    {
        $this->set('execListen', $method, $param);
        return $this;
    }
    protected function execListen($method, $param)
    {
        if(is_string($param) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $param, $metchs)){
            $variable = $this->findBoxValue($metchs[1]);
            $this->box->set(trim($metchs[1]), $this->handle->listen($method, $variable));
        }
        else{
            $this->handle->listen($method, $param);
        }
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    /**
     * @return Event
     */
    public function run($event, $method, $param = [])
    {
        $this->set('execRun', $event, $method, $param);
        return $this;
    }
    protected function execRun($event, $method, $param)
    {
        if(is_string($param) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $param, $metchs)){
            $variable = $this->findBoxValue($metchs[1]);
            $this->box->set(trim($metchs[1]), $this->handle->run($event, $method, $variable));
        }
        else{
            $this->handle->run($event, $method, $param);
        }
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
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
}