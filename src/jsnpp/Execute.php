<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Execute
{
    private $app;
    private $response;
    public function __construct(Application $app, Response $response)
    {
        $this->app = $app;
        $this->response = $response;
    }
    public function act($name, $arr = [], $param = '')
    {
        $name = trim($name);
        $name = str_replace('\\', '/', $name);
        if(strpos($name, '/') !== false){
            $arr = explode('/', $name);
            $class = $arr[0];
            $func = $arr[1];
        }
        else{
            $class = $this->app->getConfig('defaultcontroller');
            if(empty($class)){
                $class = 'index';
            }
            $func = $name;
        }
        $re = $this->app->appMethodRaw($class, $func, [$arr]);
        
        return $re;
    }
}