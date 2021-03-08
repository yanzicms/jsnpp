<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Tools
{
    public static $url;
    public static $lang;
    public static function dirName($path, $levels = 1)
    {
        while($levels > 0){
            $path = dirname($path);
            $levels --;
        }
        return $path . DIRECTORY_SEPARATOR;
    }
    public static function dump($str)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';
    }
    public static function load($file)
    {
        return include $file;
    }
    public static function loadini($file)
    {
        return parse_ini_file($file);
    }
    public static function isJson($str)
    {
        if(is_string($str)){
            $json = json_decode($str);
            $err = json_last_error();
            if(!is_null($json) && $err == JSON_ERROR_NONE){
                return true;
            }
        }
        return false;
    }
    public static function hasString($str, $arr)
    {
        foreach($arr as $val){
            if(stripos($str, $val) !== false){
                return true;
            }
        }
        return false;
    }
    public static function toArrTrim($string, $delimiter)
    {
        $reArr = explode($delimiter, $string);
        return array_map(function($v){
            return trim($v);
        },$reArr);
    }
    public static function oneSpace($string)
    {
        return preg_replace('/( )+/', ' ', trim($string));
    }
    private static function eqorhas($str, $in)
    {
        $str = trim($str);
        if(is_array($in)){
            foreach($in as $key => $val){
                if($str == $key){
                    return true;
                }
            }
        }
        else{
            if($str == trim($in)){
                return true;
            }
        }
        return false;
    }
    public static function writeConfig($configFile, $name, $value = '')
    {
        if(!is_writable($configFile) && function_exists('chmod')){
            @chmod($configFile, 0755);
        }
        $config = file($configFile);
        array_pop($config);
        $outconfig = '';
        $have = false;
        foreach($config as $key => $val){
            if(strpos($val, '=>') === false){
                $outconfig .= $val;
            }
            else{
                $item = explode('=>', $val);
                $oname = trim(trim(trim($item[0]), '\''));
                if(self::eqorhas($oname, $name)){
                    if(is_array($name)){
                        $value = $name[$oname];
                    }
                    $type = gettype($value);
                    $right = $item[0] . '=> ';
                    switch($type){
                        case 'integer':
                            $right .= $value . ',';
                            break;
                        case 'array':
                            $tempv = '[';
                            foreach($value as $skey => $sval){
                                $tempv .= '\'' . $sval . '\',';
                            }
                            $tempv = rtrim($tempv, ',') . ']';
                            $right .= $tempv . ',';
                            break;
                        case 'boolean':
                            $right .= (($value == true) ? 'true' : 'false') . ',';
                            break;
                        default:
                            $right .= '\'' . $value . '\',';
                    }
                    $outconfig .= $right . PHP_EOL;
                    $have = true;
                }
                else{
                    $outconfig .= $val;
                }
            }
        }
        if(!$have){
            $outconfig .= '    \'' . $name . '\' => ';
            $type = gettype($value);
            switch($type){
                case 'integer':
                    $outconfig .= $value . ',';
                    break;
                case 'array':
                    $tempv = '[';
                    foreach($value as $skey => $sval){
                        $tempv .= '\'' . $sval . '\',';
                    }
                    $tempv = rtrim($tempv, ',') . ']';
                    $outconfig .= $tempv . ',';
                    break;
                case 'boolean':
                    $outconfig .= (($value == true) ? 'true' : 'false') . ',';
                    break;
                default:
                    $outconfig .= '\'' . $value . '\',';
            }
            $outconfig .= PHP_EOL;
        }
        $outconfig .= '];';
        file_put_contents($configFile, $outconfig);
    }
    public static function lang($str)
    {
        return self::$lang->translate($str);
    }
    public static function url($name, $arr = [])
    {
        return self::$url->url($name, $arr);
    }
}