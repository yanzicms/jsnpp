<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp\cache;

use jsnpp\Application;
use FilesystemIterator;

class File
{
    private $app;
    private $cachePath;
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->cachePath = $this->app->rootDir() . DIRECTORY_SEPARATOR . 'assist' . DIRECTORY_SEPARATOR . 'cache';
    }
    public function get($key, $default = null)
    {
        $key = trim($key);
        $key = md5($key);
        $cachePath = $this->getPath($key);
        $cacheFile = $cachePath . DIRECTORY_SEPARATOR . $key . '.php';
        if(!is_file($cacheFile)){
            return $default;
        }
        $cacheArr = file($cacheFile);
        if(trim($cacheArr[2]) < time()){
            @unlink($cacheFile);
            return $default;
        }
        $cacheArr = array_slice($cacheArr, 3);
        $cache = implode('', $cacheArr);
        return unserialize(trim($cache));
    }
    public function set($key, $value, $ttl = 300, $tag = null)
    {
        $keytmp = trim($key);
        $key = md5($keytmp);
        $cachePath = $this->getPath($key);
        $cacheFile = $cachePath . DIRECTORY_SEPARATOR . $key . '.php';
        $cacheTime = time() + intval($ttl);
        $data = '<?php' . PHP_EOL;
        $data .= 'exit();' . PHP_EOL;
        $data .= $cacheTime . PHP_EOL;
        $data .= serialize($value);
        file_put_contents($cacheFile, $data);
        if(!empty($tag)){
            $this->tag($tag, $keytmp);
        }
    }
    private function tag($tag, $key)
    {
        $hastag = $this->get($tag);
        if(is_array($hastag)){
            if(!in_array($key, $hastag)){
                $hastag[] = $key;
            }
        }
        else{
            $hastag = [$key];
        }
        $this->set($tag, $hastag);
    }
    public function deleteTag($tag)
    {
        $dtag = $this->get($tag);
        if(is_array($dtag)){
            foreach($dtag as $val){
                $this->delete($val);
            }
        }
        $this->delete($tag);
    }
    public function delete($key)
    {
        $key = trim($key);
        $key = md5($key);
        $cachePath = $this->getPath($key);
        $cacheFile = $cachePath . DIRECTORY_SEPARATOR . $key . '.php';
        if(is_file($cacheFile)){
            @unlink($cacheFile);
        }
    }
    public function clear()
    {
        $this->delDir($this->cachePath);
    }
    public function getMultiple($keys, $default = null)
    {
        if(is_array($keys)){
            $rearr = [];
            foreach($keys as $val){
                $rearr[] = $this->get(strval($val), $default);
            }
            return $rearr;
        }
        else{
            return $this->get(strval($keys), $default);
        }
    }
    public function setMultiple($values, $ttl = null)
    {
        if(is_array($values)){
            foreach($values as $key =>$val){
                $this->set($key, $val, $ttl);
            }
        }
    }
    public function deleteMultiple($keys)
    {
        if(is_array($keys)){
            foreach($keys as $val){
                $this->delete($val);
            }
        }
        else{
            $this->delete(strval($keys));
        }
    }
    public function has($key)
    {
        $key = trim($key);
        $key = md5($key);
        $cachePath = $this->getPath($key);
        $cacheFile = $cachePath . DIRECTORY_SEPARATOR . $key . '.php';
        if(!is_file($cacheFile)){
            return false;
        }
        $cacheArr = file($cacheFile);
        if(intval(trim($cacheArr[2])) < time()){
            @unlink($cacheFile);
            return false;
        }
        return true;
    }
    private function getPath($key)
    {
        $cachePath = $this->cachePath . DIRECTORY_SEPARATOR . substr($key, 0, 3);
        if(!is_dir($cachePath)){
            @mkdir($cachePath, 0777, true);
        }
        return $cachePath;
    }
    private function delDir($dirname)
    {
        if(!is_dir($dirname)){
            return false;
        }
        $items = new FilesystemIterator($dirname);
        foreach($items as $item){
            if($item->isDir() && !$item->isLink()){
                $this->delDir($item->getPathname());
            }
            else{
                @unlink($item->getPathname());
            }
        }
        @rmdir($dirname);
        return true;
    }
}