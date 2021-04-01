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

class Entrance extends Connector
{
    private $session;
    private $lang;
    protected function initialize(){
        $this->session = $this->app->get('session');
        $this->lang = $this->app->get('lang');
    }
    public function check($item, $check = null, $alert = null, $ischeck = true)
    {
        $this->set('execCheck', $item, $check, $alert, $ischeck);
        return $this;
    }
    protected function execCheck($item, $check, $alert, $ischeck)
    {
        $list = [];
        $result = true;
        $message = 'ok';
        if($ischeck){
            if(!is_null($check) && is_string($check)){
                $check = trim($check);
            }
            if(is_bool($item) && is_null($alert)){
                if(!$item){
                    $this->ignore();
                    if(is_null($check)){
                        return false;
                    }
                    else{
                        $result = false;
                        $list[] = $message = $check;
                    }
                }
            }
            elseif(is_null($check) && is_null($alert)){
                $loweritem = strtolower(trim($item));
                if(in_array($loweritem, ['isajax', 'ispjax', 'ismobile'])){
                    if(($loweritem == 'isajax' && !$this->request->isAjax()) || ($loweritem == 'ispjax' && !$this->request->isPjax()) || ($loweritem == 'ismobile' && !$this->request->isMobile())){
                        $this->ignore();
                        return false;
                    }
                }
                else{
                    $exception = false;
                    list($allowed, $itemArr) = $this->request->isMethodToArr($item);
                    if(!$allowed){
                        $this->ignore();
                        return false;
                    }
                    else{
                        foreach($itemArr as $val){
                            if(!in_array($val, ['post', 'get', 'put', 'delete'])){
                                $exception = true;
                                break;
                            }
                        }
                        if($exception){
                            throw new ArgumentException('Parameter mismatch: check()');
                        }
                    }
                }
            }
            else{
                $errArr = [];
                if(is_array($check)){
                    foreach($check as $key => $val){
                        $err = $this->doCheck($item, $key, $val);
                        if($err !== false){
                            $errArr[] = $err;
                        }
                    }
                }
                else{
                    $err = $this->doCheck($item, $check, $alert);
                    if($err !== false){
                        $errArr[] = $err;
                    }
                }
                if(count($errArr) > 0){
                    $message = $errArr[0];
                    foreach($errArr as $ekey => $eval){
                        $list[] = $eval;
                    }
                    $result = false;
                }
            }
        }
        return [
            'result' => $result,
            'code' => 0,
            'message' => $message,
            'list' => $list
        ];
    }
    public function inbox($name, $value)
    {
        $this->set('execInBox', $name, $value);
        return $this;
    }
    protected function execInBox($name, $value)
    {
        $this->box->set($name, $value);
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    
    private function doCheck($item, $check, $alert)
    {
        $regex = '';
        if(strpos($check, '|') !== false){
            $tmpArr = explode('|', $check);
            $check = array_shift($tmpArr);
            $regex = implode('|', $tmpArr);
            $regex = trim($regex);
            if(substr($regex, 0, 2) == '/^' && substr($regex, -2) == '$/'){
                $regex = substr($regex, 2, -2);
            }
        }
        $check = strtolower(trim($check));
        $re = false;
        if($check == 'require' && trim($item) == ''){
            $re = $alert;
        }
        elseif(trim($item) != ''){
            switch($check){
                case 'accepted':
                    if(!in_array($item, ['1', 'on', 'yes'])){
                        $re = $alert;
                    }
                    break;
                case 'date':
                    if(strtotime($item) === false){
                        $re = $alert;
                    }
                    break;
                case 'boolean':
                case 'bool':
                    if(!in_array($item, [true, false, 0, 1, '0', '1'], true)){
                        $re = $alert;
                    }
                    break;
                case 'number':
                    if(!ctype_digit(strval($item))){
                        $re = $alert;
                    }
                    break;
                case 'alphanum':
                    if(!ctype_alnum($item)){
                        $re = $alert;
                    }
                    break;
                case 'url':
                    if(filter_var($item, FILTER_VALIDATE_URL) === false){
                        $re = $alert;
                    }
                    break;
                case 'email':
                    if(filter_var($item, FILTER_VALIDATE_EMAIL) === false){
                        $re = $alert;
                    }
                    break;
                case 'ip':
                    if(filter_var($item, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === false){
                        $re = $alert;
                    }
                    break;
                case 'integer':
                case 'int':
                    if(filter_var($item, FILTER_VALIDATE_INT) === false){
                        $re = $alert;
                    }
                    break;
                case 'macaddr':
                    if(filter_var($item, FILTER_VALIDATE_MAC) === false){
                        $re = $alert;
                    }
                    break;
                case 'float':
                    if(filter_var($item, FILTER_VALIDATE_FLOAT) === false){
                        $re = $alert;
                    }
                    break;
                case 'alpha':
                    if(!preg_match('/^[A-Za-z]+$/', $item)){
                        $re = $alert;
                    }
                    break;
                case 'alphadash':
                    if(!preg_match('/^[A-Za-z0-9\-\_]+$/', $item)){
                        $re = $alert;
                    }
                    break;
                case 'alphanumunder':
                    if(!preg_match('/^[A-Za-z][A-Za-z0-9\_]*$/', $item)){
                        $re = $alert;
                    }
                    break;
                case 'alphanumhyphen':
                    if(!preg_match('/^[A-Za-z][A-Za-z0-9\-]*$/', $item)){
                        $re = $alert;
                    }
                    break;
                case 'mobile':
                    if(!preg_match('/^1[3-9]\d{9}$/', $item)){
                        $re = $alert;
                    }
                    break;
                case 'idcard':
                    if(!preg_match('/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}$)/', $item)){
                        $re = $alert;
                    }
                    break;
                case 'zipcode':
                    if(!preg_match('/^\d{6}$/', $item)){
                        $re = $alert;
                    }
                    break;
                case 'regex':
                    if(!preg_match('/^'.$regex.'$/', $item)){
                        $re = $alert;
                    }
                    break;
                case 'captcha':
                    if(strtolower($this->session->get('_jsnpp_captcha')) != strtolower($item)){
                        if(!empty($alert)){
                            $re = $alert;
                        }
                        else{
                            $re = $this->lang->translate('Verification code error');
                        }
                    }
                    if($this->app->getConfig('useonce')){
                        $this->session->remove('_jsnpp_captcha');
                    }
                    break;
            }
        }
        return $re;
    }
}