<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Lang
{
    private $lang = [];
    public function __construct($lang = [])
    {
        if(is_array($lang) && count($lang) > 0){
            $this->lang = array_merge($this->lang, $lang);
        }
    }
    public function translate($name)
    {
        if(isset($this->lang[$name])){
            return $this->lang[$name];
        }
        return $name;
    }
    public function load($lang = [])
    {
        if(is_string($lang) && is_file($lang)){
            $lang = Tools::load($lang);
        }
        if(is_array($lang) && count($lang) > 0){
            $this->lang = array_merge($this->lang, $lang);
        }
    }
}