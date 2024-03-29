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

class View
{
    private $app;
    private $response;
    private $route;
    public function __construct(Application $app, Response $response, Route $route)
    {
        $this->app = $app;
        $this->response = $response;
        $this->route = $route;
    }
    public function template($tplfile = '')
    {
        Tools::$lang = $this->app->get('lang');
        Tools::$url = $this->app->get('route');
        return $this->response->template($tplfile);
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
        Tools::$lang = $this->app->get('lang');
        Tools::$act = $this->app->get('execute');
        if(is_file($tplfile)){
            $captchaurl = $this->route->url('captcha');
            $this->response->setAssign('captcha', '<img src="'.$captchaurl.'" onclick="this.src = \''.$captchaurl.'?\' + Math.random();" id="captcha" style="cursor: pointer">');
            $this->response->display($tplfile);
        }
        elseif(is_string($tplfile)){
            $this->response->receive($this->response->template($tplfile))->output();
            exit();
        }
        else{
            throw new FileNotFoundException('Parameter mismatch:' . $tplfile);
        }
    }
    public function assign($name, $value = null)
    {
        if(!is_array($name) && is_null($value)){
            $detr = debug_backtrace();
            $class = str_replace('/', '\\', $detr[1]['class']);
            $this->app->appMethod($class, $name);
        }
        else{
            $this->response->setAssign($name, $value);
        }
        return $this;
    }
    public function appendAssign($name, $value = '')
    {
        $this->response->appendAssign($name, $value);
        return $this;
    }
    public function hasAssign($name)
    {
        return $this->response->hasAssign($name);
    }
    public function noAssign($name, $value = '')
    {
        if(!$this->response->hasAssign($name)){
            $this->response->setAssign($name, $value);
        }
        return $this;
    }
    public function setCode($code)
    {
        $this->response->setCode($code);
        return $this;
    }
}