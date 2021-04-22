<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

use jsnpp\exception\RouteException;

class Route
{
    private $app;
    private $request;
    public function __construct(Application $app, Request $request)
    {
        $this->app = $app;
        $this->request = $request;
    }
    public function url($name, $arr = [])
    {
        $name = trim($name);
        $route = $this->app->getRouting($name);
        $url = $this->base();
        $surl = '';
        $suffix = '.' . trim(trim($this->app->getConfig('suffix')), '.');
        if(!is_null($route)){
            foreach($arr as $akey => $aval){
                $route = str_replace('{' . $akey . '}', $aval, $route);
            }
            if(strpos($route, '{') !== false){
                preg_match_all('/({.*?})/', $route, $matches);
                throw new RouteException('Route parameters do not match: ' . implode(', ', $matches[1]));
            }
            $url .= $route . $suffix;
        }
        else{
            if(count($arr) > 0){
                $surl = $this->getpurl($arr);
            }
            if($name == '/'){
                $url .= empty($surl) ? '' : $surl . $suffix;
            }
            else{
                $url .= empty($surl) ? $name . $suffix : $name . '/' . $surl . $suffix;
            }
        }
        if(substr($url, -11) == '/index.php/'){
            $url = substr($url, 0, -10);
        }
        return $url;
    }
    private function getpurl($arr)
    {
        $url = '';
        foreach($arr as $key => $val){
            $url .= empty($url) ? $key . '/' . $val : '/' . $key . '/' . $val;
        }
        return $url;
    }
    private function base($consider = true)
    {
        $siteroot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
        $webroot = rtrim(str_replace('\\', '/', $this->app->rootDir()), '/');
        $subroot = trim(substr($webroot, strlen($siteroot)), '/');
        if($consider && ($this->app->getConfig('rewrite') == false || strpos($this->request->requestUri(), '/index.php/') !== false)){
            $subroot .= '/index.php';
        }
        if(empty($subroot)){
            $subroot = '/';
        }
        elseif($subroot == '/index.php'){
            $subroot .= '/';
        }
        else{
            $subroot = '/' . $subroot . '/';
        }
        return $subroot;
    }
    public function redirect($name, $arr = [])
    {
        $to = $this->url($name, $arr);
        header("Location: $to");
        exit();
    }
    public function rootUrl()
    {
        return $this->base(false);
    }
    public function rootUrlFull()
    {
        return ($this->request->isHttps() ? 'https://' : 'http://') . $this->request->host() . $this->base(false);
    }
}