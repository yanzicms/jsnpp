<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Cookie
{
    public function set($name, $value, $expire)
    {
        setcookie($name, $value, time() + $expire, '/');
        return $this;
    }
    public function get($name = null)
    {
        if(is_null($name)){
            return $_COOKIE;
        }
        if(isset($_COOKIE[$name])){
            return $_COOKIE[$name];
        }
        return null;
    }
    public function has($name)
    {
        return isset($_COOKIE[$name]) ? true : false;
    }
    public function remove($name)
    {
        setcookie($name, '', time() - 3600, '/');
        return $this;
    }
}