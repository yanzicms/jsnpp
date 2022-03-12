<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Request
{
    private $method = 'get';
    private $realIP;
    protected $proxyServerIp = [];
    protected $proxyServerIpHeader = ['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP'];
    public function __construct(){
        $this->judgment();
    }
    public function requestUri()
    {
        if(isset($_SERVER['REQUEST_URI'])){
            return $_SERVER['REQUEST_URI'];
        }
        elseif(isset($_SERVER['HTTP_X_REWRITE_URL'])){
            return $_SERVER['HTTP_X_REWRITE_URL'];
        }
        elseif(isset($_SERVER['REDIRECT_URL'])){
            return $_SERVER['REDIRECT_URL'];
        }
        elseif(isset($_SERVER['ORIG_PATH_INFO'])){
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if(!empty($_SERVER['QUERY_STRING'])){
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
            return $requestUri;
        }
        return false;
    }
    private function judgment()
    {
        if(isset($_POST['_method'])){
            $_POST['_method'] = strtolower($_POST['_method']);
            if(in_array($_POST['_method'], ['post', 'get', 'put', 'delete'])){
                $this->method = $_POST['_method'];
            }
        }
        elseif(isset($_SERVER['REQUEST_METHOD'])){
            $this->method = strtolower($_SERVER['REQUEST_METHOD']);
        }
    }
    public function getMethod()
    {
        return $this->method;
    }
    public function resetRequest()
    {
        $this->judgment();
    }
    public function isMethod($method)
    {
        if(is_string($method)){
            $method = explode(',', $method);
        }
        $method = array_map(function($v){
            return strtolower(trim($v));
        },$method);
        if(in_array($this->method, $method)){
            return true;
        }
        return false;
    }
    public function isMethodToArr($method)
    {
        if(is_string($method)){
            $method = explode(',', $method);
        }
        $method = array_map(function($v){
            return strtolower(trim($v));
        },$method);
        if(in_array($this->method, $method)){
            return [true, $method];
        }
        return [false, $method];
    }
    public function isPost()
    {
        if($this->method == 'post'){
            return true;
        }
        return false;
    }
    public function isGet()
    {
        if($this->method == 'get'){
            return true;
        }
        return false;
    }
    public function isPut()
    {
        if($this->method == 'put'){
            return true;
        }
        return false;
    }
    public function isDelete()
    {
        if($this->method == 'delete'){
            return true;
        }
        return false;
    }
    public function isHttps()
    {
        if(isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 1 || strtolower($_SERVER['HTTPS']) == 'on')){
            return true;
        }
        elseif(isset($_SERVER['HTTP_X_CLIENT_SCHEME']) && strtolower($_SERVER['HTTP_X_CLIENT_SCHEME']) == 'https'){
            return true;
        }
        elseif(isset($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) == 'https'){
            return true;
        }
        elseif(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443){
            return true;
        }
        elseif(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https'){
            return true;
        }
        elseif(isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off'){
            return true;
        }
        return false;
    }
    public function isJson()
    {
        return false !== stripos($_SERVER['HTTP_ACCEPT'], 'json');
    }
    public function isAjax()
    {
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
            return true;
        }
        return false;
    }
    public function isPjax()
    {
        return !empty($_SERVER['HTTP_X_PJAX']) ? true : false;
    }
    private function isValidIP($ip, $type = '')
    {
        $type = strtolower($type);
        switch($type){
            case 'ipv4':
                $flag = FILTER_FLAG_IPV4;
                break;
            case 'ipv6':
                $flag = FILTER_FLAG_IPV6;
                break;
            default:
                $flag = 0;
                break;
        }
        $re = true;
        if(filter_var($ip, FILTER_VALIDATE_IP, $flag) === false){
            $re = false;
        }
        return $re;
    }
    private function ip2bin($ip)
    {
        if($this->isValidIP($ip, 'ipv6')){
            $IPHex = str_split(bin2hex(inet_pton($ip)), 4);
            foreach($IPHex as $key => $value){
                $IPHex[$key] = intval($value, 16);
            }
            $IPBin = vsprintf('%016b%016b%016b%016b%016b%016b%016b%016b', $IPHex);
        }
        else{
            $IPHex = str_split(bin2hex(inet_pton($ip)), 2);
            foreach ($IPHex as $key => $value) {
                $IPHex[$key] = intval($value, 16);
            }
            $IPBin = vsprintf('%08b%08b%08b%08b', $IPHex);
        }
        return $IPBin;
    }
    public function ip()
    {
        if(!empty($this->realIP)){
            return $this->realIP;
        }
        $this->realIP = $_SERVER['REMOTE_ADDR'];
        $proxyIp = $this->proxyServerIp;
        $proxyIpHeader = $this->proxyServerIpHeader;
        if(count($proxyIp) > 0 && count($proxyIpHeader) > 0){
            foreach ($proxyIpHeader as $header) {
                $tempIP = $_SERVER[$header];
                if(empty($tempIP)){
                    continue;
                }
                $tempIP = trim(explode(',', $tempIP)[0]);
                if(!$this->isValidIP($tempIP)){
                    $tempIP = null;
                }
                else{
                    break;
                }
            }
            if(!empty($tempIP)){
                $realIPBin = $this->ip2bin($this->realIP);
                foreach($proxyIp as $ip){
                    $serverIPElements = explode('/', $ip);
                    $serverIP = $serverIPElements[0];
                    $serverIPPrefix = isset($serverIPElements[1]) ? $serverIPElements[1] : 128;
                    $serverIPBin = $this->ip2bin($serverIP);
                    if(strlen($realIPBin) !== strlen($serverIPBin)){
                        continue;
                    }
                    if(strncmp($realIPBin, $serverIPBin, (int) $serverIPPrefix) === 0){
                        $this->realIP = $tempIP;
                        break;
                    }
                }
            }
        }
        if(!$this->isValidIP($this->realIP)){
            $this->realIP = '0.0.0.0';
        }
        return $this->realIP;
    }
    public function isMobile()
    {
        if(!empty($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")){
            return true;
        }
        elseif(!empty($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")){
            return true;
        }
        elseif(!empty($_SERVER['HTTP_X_WAP_PROFILE']) || !empty($_SERVER['HTTP_PROFILE'])){
            return true;
        }
        elseif(!empty($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])){
            return true;
        }
        return false;
    }
    public function host()
    {
        if(isset($_SERVER['HTTP_X_FORWARDED_HOST'])){
            return $_SERVER['HTTP_X_FORWARDED_HOST'];
        }
        elseif(isset($_SERVER['HTTP_HOST'])){
            return $_SERVER['HTTP_HOST'];
        }
        else{
            return $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] == '80' ? '' : ':' . $_SERVER['SERVER_PORT']);
        }
    }
}