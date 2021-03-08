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
    public function assign($name, $value = '')
    {
        $this->set('execAssign', $name, $value);
        return $this;
    }
    protected function execAssign($name, $value)
    {
        if(is_string($value) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $value, $metchs)){
            $value = $this->findBoxValue($metchs[1]);
        }
        $this->response->setAssign($name, $value);
    }
    public function display($tplfile = '')
    {
        if(empty($tplfile)){
            $detr = debug_backtrace();
            $classArr = explode('\\', str_replace('/', '\\', $detr[1]['class']));
            $class = lcfirst(end($classArr));
            $method = $detr[1]['function'];
            $tplfile = $this->app->appDir() . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . $class .DIRECTORY_SEPARATOR . $method . '.' . $this->app->getConfig('templatesuffix');
        }
        $this->set('execDisplay', $tplfile);
        return $this;
    }
    protected function execDisplay($tplfile)
    {
        if(is_string($tplfile) && $tplfile == ':ok'){
            $this->response->receive([
                'result' => 'ok',
                'code' => 0,
                'message' => '',
                'list' => []
            ])->output();
            exit();
        }
        elseif(is_string($tplfile) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $tplfile, $metchs)){
            $this->response->receive($this->findBoxValue($metchs[1]))->output();
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
                Tools::$url = $this->app->get('route');
                $this->response->display($tplfile);
            }
            else{
                throw new FileNotFoundException('Parameter mismatch:' . $tplfile);
            }
        }
    }
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
    public function setCode($code)
    {
        $this->response->setCode($code);
        return $this;
    }
}