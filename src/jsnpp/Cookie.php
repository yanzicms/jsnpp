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
    }
    public function get($name)
    {
        return $_COOKIE[$name];
    }
    public function remove($name)
    {
        setcookie($name, '', time() - 3600, '/');
    }
}