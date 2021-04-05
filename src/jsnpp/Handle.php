<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Handle
{
    private $app;
    private $event = [];
    public function __construct(Application $app){
        $this->app = $app;
    }
    public function register($event)
    {
        if(is_array($event)){
            foreach($event as $val){
                $this->doregister($val);
            }
        }
        else{
            $this->doregister($event);
        }
    }
    private function doregister($event)
    {
        $name = str_replace('\\', '/', $event);
        if(strpos($name, '/') !== false){
            $namearr = explode('/', $name);
            $name = lcfirst($namearr[0]);
        }
        else{
            $name = lcfirst($name);
        }
        if(substr($event, -4) == '.php'){
            $event = substr($event, 0, -4);
        }
        $this->event[$name] = $this->app->handleClass($event);
    }
    public function listen($method, $param = [])
    {
        foreach($this->event as $key => $val){
            if(method_exists($val, $method)){
                $param = $this->app->handleMethod($val, $method, $param);
            }
        }
        return $param;
    }
    public function run($event, $method, $param = [])
    {
        if(substr($event, -4) == '.php'){
            $event = substr($event, 0, -4);
        }
        $class = $this->app->handleClass($event);
        if(method_exists($class, $method)){
            $param = $this->app->handleMethod($class, $method, $param);
        }
        return $param;
    }
}