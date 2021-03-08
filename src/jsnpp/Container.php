<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Container
{
    private static $instances = [];
    private function __construct(){}
    private function __clone(){}
    public static function has($id)
    {
        return isset(self::$instances[$id]);
    }
    public static function get($id)
    {
        if(self::has($id)){
            return self::$instances[$id];
        }
        else{
            return false;
        }
    }
    public static function set($id, $instance)
    {
        self::$instances[$id] = $instance;
    }
}