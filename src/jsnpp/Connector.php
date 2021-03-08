<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Connector
{
    protected $app;
    protected $request;
    protected $response;
    private $aisle;
    protected $box;
    private $ignore = false;
    private $message = [];
    private $selfMessage = [];
    private $sign = 0;
    public function __construct(Application $app, Request $request, Response $response, Aisle $aisle, Box $box){
        $this->app = $app;
        $this->request = $request;
        $this->response = $response;
        $this->aisle = $aisle;
        $this->box = $box;
        $this->initialize();
    }
    protected function initialize(){}
    protected function ignore()
    {
        $this->ignore = true;
    }
    protected function setIgnore($val)
    {
        $this->ignore = $val;
    }
    protected function resetIgnore()
    {
        $this->ignore = false;
    }
    protected function getIgnore()
    {
        return $this->ignore;
    }
    protected function addMessage($message)
    {
        $this->message[] = $message;
    }
    protected function getMessage()
    {
        return $this->message;
    }
    protected function setMessage($message)
    {
        $this->message = $message;
    }
    protected function clearMessage()
    {
        $this->message = [];
    }
    protected function getSelfMessage()
    {
        return $this->selfMessage;
    }
    protected function setSelfMessage($message)
    {
        $this->selfMessage = $message;
    }
    protected function clearSelfMessage()
    {
        $this->selfMessage = [];
    }
    protected function addSign()
    {
        $this->sign ++;
    }
    protected function resetSign()
    {
        $this->sign = 0;
    }
    protected function setSign($sign)
    {
        $this->sign = $sign;
    }
    protected function getSign()
    {
        return $this->sign;
    }
    protected function set($method, ...$param)
    {
        if($this->ignore == false){
            $this->aisle->setAisle(get_class($this), $method, $param);
        }
    }
    public function __get($name)
    {
        $this->execAisle();
        $obj = $this->app->get($name);
        $obj->setIgnore($this->ignore);
        $obj->setSelfMessage($this->getMessage());
        $this->clearMessage();
        $obj->setSign($this->getSign() + 1);
        $this->resetSign();
        return $obj;
    }
    public function finish()
    {
        $this->execAisle();
        $this->app->entrance->resetIgnore();
    }
    public function relay()
    {
        $this->execAisle();
        $this->setSelfMessage($this->getMessage());
        $this->clearMessage();
    }
    private function execAisle()
    {
        $aisle = $this->aisle->getAisle();
        if($this->ignore == false){
            $result = array_reduce($aisle, function($carray, $item){
                if($carray === false){
                    return false;
                }
                $rearray = call_user_func_array([$this->app->get($item['class']), $item['method']], $item['param']);
                if($rearray === false){
                    return false;
                }
                if($carray['result'] == true && $rearray['result'] == false){
                    $carray = $rearray;
                    if(!isset($rearray['list']) || empty($rearray['list'])){
                        $carray['list'][] = $rearray['message'];
                    }
                    else{
                        $carray['list'] = $rearray['list'];
                    }
                }
                elseif($carray['result'] == false && $rearray['result'] == false){
                    $carray['list'][] = $rearray['message'];
                }
                return $carray;
            }, [
                'result' => true,
                'code' => 0,
                'message' => 'ok',
                'list' => [],
            ]);
            if($result !== false){
                if($result['result'] == true){
                    if(method_exists($this, 'finalExec')){
                        $this->finalExec();
                    }
                }
                else{
                    $this->response->end($result);
                }
            }
        }
    }
}