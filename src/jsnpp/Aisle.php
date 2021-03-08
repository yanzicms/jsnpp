<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Aisle
{
    private $aisle = [];
    public function setAisle($class, $method, $param)
    {
        $this->aisle[] = [
            'class' => $class,
            'method' => $method,
            'param' => $param
        ];
    }
    public function getAisle()
    {
        $tmpArr= $this->aisle;
        $this->aisle = [];
        return $tmpArr;
    }
}