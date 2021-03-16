<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

use jsnpp\exception\ArgumentException;
use jsnpp\exception\ClassNotFoundException;
use jsnpp\exception\FuncNotFoundException;

class Application
{
    const VERSION = '1.8.4';
    private $startTime;
    private $startMem;
    private $rootDir;
    private $config = [];
    private $db = [];
    private $routing = [];
    public function __construct($rootPath = '')
    {
        $this->startTime = microtime(true);
        $this->startMem = memory_get_usage();
        $this->rootDir = $rootPath;
        Container::set(Application::class, $this);
    }
    public function make($name, $params = [], ...$other)
    {
        if(Container::has($name)){
            return Container::get($name);
        }
        if($params instanceof \Closure){
            $object = $this->execFunction($params, $other);
        }
        else{
            $names = $this->toClassName($name);
            if($names != $name){
                $name = $names;
                if(Container::has($name)){
                    return Container::get($name);
                }
            }
            $args = $this->combine($params, $other);
            
            $object = $this->execClass($name, $args);
        }
        Container::set($name, $object);
        return $object;
    }
    private function combine($params, $other)
    {
        $args = [];
        if(!empty($params)){
            $args[] = $params;
        }
        if(count($other) > 0){
            if($this->isIndexArray($other)){
                foreach($other as $val){
                    $args[] = $val;
                }
            }
            else{
                foreach($other as $key => $val){
                    $args[$key] = $val;
                }
            }
        }
        return $args;
    }
    private function execClass($class, $args = [])
    {
        try{
            $reflect = new \ReflectionClass($class);
        }
        catch(\ReflectionException $e){
            throw new ClassNotFoundException('class not exists: ' . $class, $e);
        }
        $constructor = $reflect->getConstructor();
        $arguments = [];
        if(!is_null($constructor)){
            $arguments = $this->getParams($constructor, $args);
            
        }
        elseif(count($args) > 0){
            throw new ArgumentException('Parameter mismatch:' . implode(', ', $args));
        }
        return $reflect->newInstanceArgs($arguments);
    }
    private function getParams($constructor, $args = [])
    {
        $arguments = [];
        $isIndex = $this->isIndexArray($args);
        $params = $constructor->getParameters();
        $parcount = 0;
        $noclass = 0;
        foreach($params as $param){
            if(!$param->isDefaultValueAvailable() && is_null($param->getClass())){
                $parcount ++;
            }
            if(is_null($param->getClass())){
                $noclass ++;
            }
        }
        $coutargs = count($args);
        if($coutargs > 0 && $coutargs < $parcount){
            throw new ArgumentException('Parameter mismatch:' . implode(', ', $args));
        }
        else{
            foreach($params as $param){
                $name = $param->getName();
                if(!is_null($param->getClass())){
                    $arguments[] = $this->getClassParam($param->getClass()->getName(), $args, $noclass);
                }
                elseif($isIndex){
                    $arguments[] = array_shift($args);
                }
                elseif(!$isIndex && isset($args[$name])){
                    $arguments[] = $args[$name];
                }
                elseif($param->isDefaultValueAvailable()){
                    $arguments[] = $param->getDefaultValue();
                }
                else{
                    throw new ArgumentException('Parameter mismatch:' . implode(', ', $args));
                }
            }
        }
        return $arguments;
    }
    private function getClassParam($class, &$args, $noclass)
    {
        if(count($args) > 0){
            reset($args);
            $key = key($args);
            if($args[$key] instanceof $class){
                $obj = array_shift($args);
            }
            else{
                $cargs = $args;
                $cargs = array_slice($cargs, $noclass);
                $obj = $this->make($class, $cargs);
            }
        }
        else{
            $obj = $this->make($class);
        }
        return $obj;
    }
    public function get($name)
    {
        $name = $this->toClassName($name);
        if(!Container::has($name)){
            $instance = $this->make($name);
        }
        else{
            $instance = Container::get($name);
        }
        return $instance;
    }
    public function __get($name)
    {
        return $this->get($name);
    }
    public function has($name)
    {
        $name = $this->toClassName($name);
        return Container::has($name);
    }
    private function toClassName($name)
    {
        $names = str_replace('/', '\\', $name);
        if(substr_count($names, '\\') == 0){
            $names = __NAMESPACE__ . '\\' . ucfirst($names);
            return $names;
        }
        else{
            return $name;
        }
    }
    private function execMethod($method, $class = null, $args = [], $classargs = [])
    {
        if(!is_null($class) && !is_object($class)){
            $class = $this->make($class, $classargs);
        }
        try{
            $reflect = new \ReflectionMethod($class, $method);
        }
        catch(\ReflectionException $e){
            $class = is_object($class) ? get_class($class) : $class;
            throw new FuncNotFoundException('method not exists: ' . $class . '::' . $method . '()', $e);
        }
        if(!$reflect->isPublic()){
            $class = is_object($class) ? get_class($class) : $class;
            throw new FuncNotFoundException('method not exists: ' . $class . '::' . $method . '()');
        }
        $arguments = $this->getParams($reflect, $args);
        
        return $reflect->invokeArgs(is_object($class) ? $class : null, $arguments);
    }
    public function execFunction($function, $args = [])
    {
        try {
            $reflect = new \ReflectionFunction($function);
        } catch (\ReflectionException $e) {
            throw new FuncNotFoundException("function not exists: {$function}()", $e);
        }
        $arguments = $this->getParams($reflect, $args);
        return $function(...$arguments);
    }
    private function isIndexArray($arr)
    {
        $re = true;
        if(count($arr) > 0){
            $narr = array_keys($arr);
            foreach($narr as $val){
                if(!is_int($val)){
                    $re = false;
                    break;
                }
            }
        }
        return $re;
    }
    private function toAppClassName($name)
    {
        $names = str_replace('/', '\\', $name);
        if(substr_count($names, '\\') == 0){
            $names = 'app\controller\\' . ucfirst($names);
            return $names;
        }
        else{
            return $name;
        }
    }
    public function appClass($name)
    {
        $name = $this->toAppClassName($name);
        if(!Container::has($name)){
            $instance = $this->make($name);
        }
        else{
            $instance = Container::get($name);
        }
        return $instance;
    }
    public function appMethod($class, $method, $params = [], ...$other)
    {
        $class = $this->toAppClassName($class);
        $args = $this->combine($params, $other);
        return $this->execMethod($method, $class, $args);
    }
    public function appMethodRaw($class, $method, $args = [])
    {
        $class = $this->toAppClassName($class);
        return $this->execMethod($method, $class, $args);
    }
    public function rootDir()
    {
        return $this->rootDir;
    }
    public function appDir()
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . 'app';
    }
    public function execTime()
    {
        if(!is_null($this->startTime)){
            return microtime(true) - $this->startTime;
        }
        return false;
    }
    public function usedMemory()
    {
        return (memory_get_usage() - $this->startMem) . ' byte';
    }
    public function setConfig($name, $value = '')
    {
        if(is_array($name)){
            $this->config = array_merge($this->config, $name);
        }
        else{
            $this->config[$name] = $value;
        }
    }
    public function getConfig($name = null)
    {
        if(is_null($name)){
            return $this->config;
        }
        elseif(isset($this->config[$name])){
            return $this->config[$name];
        }
        return null;
    }
    public function setDb($name, $value = '')
    {
        if(is_array($name)){
            $this->db = array_merge($this->db, $name);
        }
        else{
            $this->db[$name] = $value;
        }
    }
    public function getDb($name = null)
    {
        if(is_null($name)){
            return $this->db;
        }
        elseif(isset($this->db[$name])){
            return $this->db[$name];
        }
        return null;
    }
    public function setRouting($name, $value = '')
    {
        if(is_array($name)){
            $this->routing = array_merge($this->routing, $name);
        }
        else{
            $this->routing[$name] = $value;
        }
    }
    public function getRouting($name = null)
    {
        if(is_null($name)){
            return $this->routing;
        }
        elseif(isset($this->routing[$name])){
            return $this->routing[$name];
        }
        return null;
    }
    public function writeConfig($name, $value = '')
    {
        if(is_array($name)){
            foreach($name as $key => $val){
                $this->config[$key] = $val;
            }
        }
        else{
            $this->config[$name] = $value;
        }
        Tools::writeConfig($this->rootDir() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php', $name, $value);
    }
    public function writeDb($name, $value = '')
    {
        if(is_array($name)){
            foreach($name as $key => $val){
                $this->db[$key] = $val;
            }
        }
        else{
            $this->db[$name] = $value;
        }
        Tools::writeConfig($this->rootDir() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.php', $name, $value);
    }
    public function writeRouting($name, $value = '')
    {
        if(is_array($name)){
            foreach($name as $key => $val){
                $this->routing[$key] = $val;
            }
        }
        else{
            $this->routing[$name] = $value;
        }
        Tools::writeConfig($this->rootDir() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routing.php', $name, $value);
    }
    public function writeCustomize($customize, $name, $value = '')
    {
        if(substr($customize, -4) == '.php'){
            $customize = substr($customize, 0, -4);
        }
        $cusarr = $this->getConfig('customize');
        if(!in_array($customize, $cusarr)){
            $cusarr[] = $customize;
            $this->writeConfig('customize', $cusarr);
            $data = '<?php' . PHP_EOL . 'return [' . PHP_EOL . '];';
            file_put_contents($this->rootDir() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $customize . '.php', $data);
        }
        if(is_array($name)){
            foreach($name as $key => $val){
                $this->config[$key] = $val;
            }
        }
        else{
            $this->config[$name] = $value;
        }
        Tools::writeConfig($this->rootDir() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $customize . '.php', $name, $value);
    }
}