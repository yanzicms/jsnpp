<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Jsnpp
{
    private $app;
    private $request;
    private $response;
    public function __construct(Application $app, Request $request, Response $response)
    {
        $this->app = $app;
        $this->request = $request;
        $this->response = $response;
    }
    public function startup()
    {
        $configenv = $this->app->rootDir() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . '.env';
        if(is_file($configenv)){
            $this->app->setConfig(Tools::loadini($configenv));
        }
        else{
            $this->app->setConfig(Tools::load($this->app->rootDir() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php'));
        }
        $dbenv = $this->app->rootDir() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . '.env.db';
        if(is_file($dbenv)){
            $this->app->setDb(Tools::loadini($dbenv));
        }
        else{
            $this->app->setDb(Tools::load($this->app->rootDir() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.php'));
        }
        $this->app->setRouting(Tools::load($this->app->rootDir() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routing.php'));
        Tools::load(__DIR__ . DIRECTORY_SEPARATOR . 'helper.php');
        $common = $this->app->appDir() . DIRECTORY_SEPARATOR . 'common.php';
        if(is_file($common)){
            Tools::load($common);
        }
        date_default_timezone_set($this->app->getConfig('timezone'));
        error_reporting($this->app->getConfig('debug') ? E_ALL : 0);
        ob_start();
        $customize = $this->app->getConfig('customize');
        if(!empty($customize)){
            if(!is_array($customize)){
                $customize = [strval($customize)];
            }
            foreach($customize as $cval){
                $cval = trim($cval);
                if(substr($cval, -4) != '.php'){
                    $cval .= '.php';
                }
                $this->app->setConfig(Tools::load($this->app->rootDir() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $cval));
            }
        }
        $this->app->make('lang', Tools::load(__DIR__ . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . strtolower($this->app->getConfig('language')) . '.php'));
        $this->app->make('errors');
        $this->request->resetRequest();
        $jsnpp = $this->judge();
        Tools::$app = $this->app;
        $this->response->resetAssign()->receive($this->app->appMethod($jsnpp['controller'], $jsnpp['method'], $jsnpp['parameter']))->output();
    }
    public function uriarr()
    {
        $uristr = trim(parse_url($this->request->requestUri(), PHP_URL_PATH), '/');
        $position = - (strlen($this->app->getConfig('suffix')) + 1);
        if(substr($uristr, $position) == '.' . $this->app->getConfig('suffix')){
            $uristr = substr($uristr, 0, $position);
        }
        $uriarr = explode('/', $uristr);
        $siteroot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
        $webroot = rtrim(str_replace('\\', '/', $this->app->rootDir()), '/');
        $subroot = trim(substr($webroot, strlen($siteroot)), '/');
        if(!empty($subroot)){
            $uriarr = array_slice($uriarr, substr_count($subroot, '/') + 1);
        }
        if(isset($uriarr[0]) && $uriarr[0] == 'index.php'){
            array_shift($uriarr);
        }
        return $uriarr;
    }
    private function isempty($uriarr)
    {
        return empty($uriarr) || (is_array($uriarr) && count($uriarr) == 1 && empty($uriarr[0]));
    }
    public function judge()
    {
        $routing = [];
        $uriarr = $this->uriarr();
        if($this->isempty($uriarr)){
            $routing['controller'] = 'Index';
            $routing['method'] = 'index';
            $routing['parameter'] = [];
        }
        elseif(count($uriarr) == 1 && strtolower($uriarr[0]) == 'captcha'){
            call_user_func([$this->app->get('captcha'), 'generate']);
            exit();
        }
        elseif(count($uriarr) == 1 && in_array(strtolower($uriarr[0]), Tools::arraytolower($this->app->getConfig('alone')))){
            $routing['controller'] = 'index';
            $routing['method'] = $uriarr[0];
            $routing['parameter'] = [];
        }
        elseif(count($uriarr) == 1 && in_array(strtolower($uriarr[0]), Tools::arraytolower($this->app->getConfig('controller')))){
            $routing['controller'] = $uriarr[0];
            $routing['method'] = 'index';
            $routing['parameter'] = [];
        }
        else{
            $ismatch = false;
            if(count($uriarr) == 2){
                if(false !== $routkey = $this->istwosegment($uriarr[0])){
                    $ismatch = true;
                    $routing['controller'] = 'Index';
                    $catearr = explode('/', $routkey[1]);
                    $key = trim(trim(trim($catearr[1]), '{}'));
                    if($key == 'id' && !preg_match('/^\d{1,}$/i', $uriarr[1])){
                        $routing['method'] = 'fail';
                        $routing['parameter'] = [];
                    }
                    else{
                        $routkeyarr = explode('/', $routkey[0]);
                        $routing['method'] = $routkeyarr[1];
                        $routing['parameter'] = [
                            $key => $uriarr[1]
                        ];
                    }
                }
            }
            if(!$ismatch){
                $mainroute = $this->app->getConfig('mainroute');
                $mainrouting = $this->app->getRouting('index/' . $mainroute);
                $mainrouting = str_replace('\\', '/', $mainrouting);
                $mainroutarr = explode('/', $mainrouting);
                list($ismainroute, $parameter) = $this->ismainroute($uriarr, $mainroutarr);
                if($ismainroute){
                    $ismatch = true;
                    $routing['controller'] = 'Index';
                    $routing['method'] = $mainroute;
                    $routing['parameter'] = $parameter;
                }
            }
            if(!$ismatch){
                $routing = $this->ordinaryAnalysis($uriarr);
            }
            
        }
        $input = file_get_contents('php://input');
        $putarr = [];
        if(!empty($input)){
            $putarr = json_decode($input, true);
            if(is_null($putarr)){
                parse_str($input, $putarr);
            }
        }
        $routing['parameter'] =array_merge($routing['parameter'], $_GET, $_POST, $putarr);
        return $routing;
    }
    private function istwosegment($str)
    {
        $strl = strlen($str) + 1;
        $twosegment = $this->app->getConfig('twosegment');
        foreach($twosegment as $key => $val){
            $rout = $this->app->getRouting('index/' . $val);
            $rout = str_replace('\\', '/', $rout);
            if(substr($rout, 0, $strl) == $str . '/' && substr_count($rout, '/') == 1){
                return ['index/' . $val, $rout];
            }
        }
        return false;
    }
    
    private function ordinaryAnalysis($tmparr)
    {
        $routing['controller'] = array_shift($tmparr);
        $routing['method'] = array_shift($tmparr);
        $routing['parameter'] = [];
        $tmpparam = '';
        while(count($tmparr) > 0){
            if(empty($tmpparam)){
                $tmpparam = array_shift($tmparr);
            }
            else{
                $routing['parameter'][$tmpparam] = array_shift($tmparr);
                $tmpparam = '';
            }
        }
        if(!empty($tmpparam)){
            $routing['parameter'][$tmpparam] = '';
        }
        return $routing;
    }
    private function ismainroute($uriarr, $mainroutarr)
    {
        $ismatch = true;
        $parameter = [];
        if(count($uriarr) != count($mainroutarr)){
            $ismatch = false;
        }
        else{
            foreach($mainroutarr as $key => $val){
                $val = trim($val);
                if(substr($val, 0, 1) == '{' && substr($val, -1) == '}'){
                    $val = trim(trim($val, '{}'));
                    switch($val){
                        case 'year':
                            $regstr = '\d{4}';
                            break;
                        case 'month':
                            $regstr = '(0?[1-9]|1[0-2])';
                            break;
                        case 'day':
                            $regstr = '((0?[1-9])|((1|2)[0-9])|30|31)';
                            break;
                        case 'id':
                            $regstr = '\d{1,}';
                            break;
                        case 'name':
                        case 'category':
                            $regstr = '[A-Za-z]([A-Za-z0-9_\-]*[A-Za-z0-9])?';
                            break;
                        case 'author':
                            $regstr = '(_admin|_index|[A-Za-z]([A-Za-z0-9_\-]*[A-Za-z0-9])?)';
                            break;
                        default:
                            $regstr = '';
                            break;
                    }
                    if(empty($regstr)){
                        $ismatch = false;
                        break;
                    }
                    else{
                        $regstr = '/^' . $regstr . '$/i';
                        if(!preg_match($regstr, $uriarr[$key])){
                            $ismatch = false;
                            break;
                        }
                        else{
                            $parameter[$val] = $uriarr[$key];
                        }
                    }
                }
                else{
                    if($val != $uriarr[$key]){
                        $ismatch = false;
                        break;
                    }
                }
            }
        }
        if(!$ismatch){
            $parameter = [];
        }
        return [$ismatch, $parameter];
    }
    
}