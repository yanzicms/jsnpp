<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

/**
 * @property Application app
 */

class Controller
{
    protected $app;
    protected $request;
    protected $view;
    protected $lang;
    protected $box;
    protected $session;
    protected $cookie;
    protected $route;
    protected $handle;
    protected $rootDir;
    protected $appDir;
    protected $DS;
    protected $tablePrefix;
    public function __construct(Application $app, Request $request, View $view, Lang $lang, Box $box, Session $session, Cookie $cookie, Route $route, Handle $handle){
        $this->app = $app;
        $this->request = $request;
        $this->view = $view;
        $this->lang = $lang;
        $this->box = $box;
        $this->session = $session;
        $this->cookie = $cookie;
        $this->route = $route;
        $this->handle = $handle;
        $this->rootDir = $this->app->rootDir();
        $this->appDir = $this->app->appDir();
        $this->DS = DIRECTORY_SEPARATOR;
        $classArr = explode('\\', str_replace('/', '\\', get_class($this)));
        $class = lcfirst(end($classArr));
        $this->lang->load($this->appDir . $this->DS . 'lang' . $this->DS . $class . $this->DS . $this->app->getConfig('language') . '.php');
        $this->tablePrefix = $this->app->getDb('prefix');
        $this->initialize();
    }
    protected function initialize(){}
}