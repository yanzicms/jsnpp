<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Session
{
    private $isstart = false;
    private function start()
    {
        if($this->isstart == false){
            session_start();
            $this->isstart = true;
        }
    }
    public function set($name, $value)
    {
        $this->start();
        $_SESSION[$name] = $value;
        return $this;
    }
    public function get($name = null)
    {
        $this->start();
        if(is_null($name)){
            return $_SESSION;
        }
        if(isset($_SESSION[$name])){
            return $_SESSION[$name];
        }
        return null;
    }
    public function has($name)
    {
        $this->start();
        return isset($_SESSION[$name]) ? true : false;
    }
    public function remove($name)
    {
        $this->start();
        if(isset($_SESSION[$name])){
            unset($_SESSION[$name]);
        }
        return $this;
    }
}