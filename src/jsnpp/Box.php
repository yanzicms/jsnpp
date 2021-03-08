<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Box
{
    private $box = [];
    public function get($name = null)
    {
        if(is_null($name)){
            return $this->box;
        }
        else{
            $name = trim($name);
            if(isset($this->box[$name])){
                return $this->box[$name];
            }
            return null;
        }
    }
    public function set($name, $value)
    {
        $name = trim($name);
        $this->box[$name] = $value;
        return $this->box[$name];
    }
}