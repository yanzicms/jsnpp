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
            if(strpos($name, '.') !== false){
                $varr = explode('.', $name);
                $value = array_shift($varr);
                if(isset($this->box[$value])){
                    $value = $this->box[$value];
                    if(count($varr) > 0){
                        foreach($varr as $val){
                            if(isset($value[$val])){
                                $value = $value[$val];
                            }
                            else{
                                $value = null;
                                break;
                            }
                        }
                    }
                }
                else{
                    $value = null;
                }
                return $value;
            }
            elseif(isset($this->box[$name])){
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
    public function has($name)
    {
        if(strpos($name, '.') !== false){
            $has = true;
            $varr = explode('.', $name);
            $value = array_shift($varr);
            if(isset($this->box[$value])){
                $newvalue = $this->box[$value];
                while(count($varr) > 0){
                    $value = array_shift($varr);
                    if(isset($newvalue[$value])){
                        continue;
                    }
                    else{
                        $has = false;
                        break;
                    }
                }
            }
            else{
                $has = false;
            }
            return $has;
        }
        else{
            return !!isset($this->box[$name]);
        }
    }
}