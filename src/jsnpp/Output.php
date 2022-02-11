<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

use jsnpp\exception\FileNotFoundException;

class Output extends Connector
{
    private $route;
    public function initialize(){
        $this->route = $this->app->get('route');
    }
    /**
     * @return Output
     */
    public function noAssign($name, $value = '')
    {
        $this->set('execNoAssign', $name, $value);
        return $this;
    }
    protected function execNoAssign($name, $value)
    {
        if(is_string($value) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $value, $metchs)){
            $value = $this->findBoxValue($metchs[1]);
        }
        if(!$this->response->hasAssign($name)){
            $this->response->setAssign($name, $value);
        }
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    /**
     * @return Output
     */
    public function appendAssign($name, $value = '')
    {
        $this->set('execAppendAssign', $name, $value);
        return $this;
    }
    protected function execAppendAssign($name, $value)
    {
        if(is_string($value) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $value, $metchs)){
            $value = $this->findBoxValue($metchs[1]);
        }
        $this->response->appendAssign($name, $value);
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    /**
     * @return Output
     */
    public function assign($name, $value = null)
    {
        if(!is_array($name) && is_null($value)){
            $detr = debug_backtrace();
            $class = str_replace('/', '\\', $detr[1]['class']);
        }
        else{
            $class = null;
        }
        $this->set('execAssign', $name, $value, $class);
        return $this;
    }
    protected function execAssign($name, $value, $class)
    {
        if(!is_array($name) && is_null($value) && !is_null($class)){
            $this->app->appMethod($class, $name);
        }
        else{
            if(is_string($value) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $value, $metchs)){
                $value = $this->findBoxValue($metchs[1]);
            }
            $this->response->setAssign($name, $value);
        }
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    /**
     * @return Output
     */
    public function display($tplfile = '', $append = [])
    {
        if(empty($tplfile)){
            $detr = debug_backtrace();
            $classArr = explode('\\', str_replace('/', '\\', $detr[1]['class']));
            $class = lcfirst(end($classArr));
            $method = $detr[1]['function'];
            $tplfile = $this->app->appDir() . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . $class .DIRECTORY_SEPARATOR . $method . '.' . $this->app->getConfig('templatesuffix');
        }
        $this->set('execDisplay', $tplfile, $append);
        return $this;
    }
    protected function execDisplay($tplfile, $append)
    {
        if(is_string($tplfile) && $tplfile == ':ok'){
            $rearr = [
                'result' => 'ok',
                'code' => 0,
                'message' => '',
                'list' => []
            ];
            if(is_array($append) && count($append) > 0){
                foreach($append as $key => $val){
                    if(preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $val, $metchs)){
                        $append[$key] = $this->findBoxValue($metchs[1]);
                    }
                }
                $rearr = array_merge($rearr, $append);
            }
            $this->response->receive($rearr)->output();
            exit();
        }
        elseif(is_string($tplfile) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $tplfile, $metchs)){
            $this->response->receive($this->findBoxValue($metchs[1]))->output();
            exit();
        }
        elseif(is_array($tplfile)){
            foreach($tplfile as $key => $val){
                if(preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $val, $metchs)){
                    $tplfile[$key] = $this->findBoxValue($metchs[1]);
                }
            }
            $this->response->receive($tplfile)->output();
            exit();
        }
        else{
            $suffix = '.' . trim(trim($this->app->getConfig('templatesuffix')), '.');
            $suffixlen = - strlen($suffix);
            if(is_array($tplfile) || (is_string($tplfile) && substr($tplfile, $suffixlen) != $suffix)){
                $this->response->receive($tplfile)->output();
                exit();
            }
            elseif(is_file($tplfile)){
                $captchaurl = $this->route->url('captcha');
                $this->response->setAssign('captcha', '<img src="'.$captchaurl.'" onclick="this.src = \''.$captchaurl.'?\' + Math.random();" style="cursor: pointer">');
                Tools::$lang = $this->app->get('lang');
                Tools::$act = $this->app->get('execute');
                $this->response->display($tplfile);
            }
            else{
                throw new FileNotFoundException('Parameter mismatch:' . $tplfile);
            }
        }
    }
    /**
     * @return Output
     */
    public function redirect($name, $array = [])
    {
        $this->set('execRedirect', $name, $array);
        return $this;
    }
    protected function execRedirect($name, $array)
    {
        $this->route->redirect($name, $array);
        exit();
    }
    /**
     * @return Output
     */
    public function dump($value)
    {
        $this->set('execDump', $value);
        return $this;
    }
    protected function execDump($value)
    {
        if(is_string($value) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $value, $metchs)){
            $value = $this->findBoxValue($metchs[1]);
        }
        dump($value);
        exit();
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
    /**
     * @return Output
     */
    public function setCode($code)
    {
        $this->response->setCode($code);
        return $this;
    }
}