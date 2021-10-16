<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
use jsnpp\Tools;

function dump($str){
    Tools::dump($str);
}
function lang($str){
    return Tools::lang($str);
}
function url($name, $arr = []){
    return Tools::url($name, $arr);
}
function act($name, $arr = [], $param = ''){
    return Tools::act($name, $arr, $param);
}
function subtext($text, $length)
{
    if(mb_strlen($text, 'utf8') > $length)
        return mb_substr($text, 0, $length, 'utf8').'...';
    return $text;
}