<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Errors
{
    private $app;
    private $request;
    private $lang;
    private $route;
    public function __construct(Application $app, Request $request, Lang $lang, Route $route)
    {
        $this->app = $app;
        $this->request = $request;
        $this->lang = $lang;
        $this->route = $route;
        @set_error_handler([$this, 'error_handler']);
        @set_exception_handler([$this, 'exception_handler']);
    }
    public function exception_handler($exception)
    {
        if($this->app->getConfig('missed') == '404'){
            $this->route->redirect('index/fail');
        }
        else{
            ob_clean();
            if($this->app->getConfig('debug') == false){
                echo 'Error';
            }
            else{
                if($this->request->isAjax()){
                    echo $this->lang->translate('Uncaught exception'). ': ' . $exception->getMessage();
                }
                else{
                    echo '<div style="padding: 1.5rem;background-color: lightgoldenrodyellow">' . $this->lang->translate('Uncaught exception'). ': ' . $exception->getMessage() . '</div>';
                }
            }
            ob_end_flush();
        }
        exit();
    }
    public function error_handler($errno, $errstr, $errfile, $errline)
    {
        $this->log($this->lang->translate('Error message'). ': ' . $errstr . '[' . $this->lang->translate('Error'). ': ' . $errno . '] ' . $this->lang->translate('File location'). ': ' . $errfile . '[' . $this->lang->translate('Line'). ': ' . $errline . '] ' . $this->lang->translate('Execution time'). ': ' . date("Y-m-d h:i:sa") . PHP_EOL);
        if(!(error_reporting() & $errno)){
            return false;
        }
        ob_clean();
        $intercept = strlen($this->app->rootDir()) + 1;
        if($this->request->isAjax()){
            echo htmlspecialchars($errstr) . ' [' . $this->lang->translate('Error'). ': ' . $errno . '] ' . substr($errfile, $intercept) . ' [' . $this->lang->translate('Line'). ': ' . $errline . ']';
        }
        else{
            echo '<div style="padding: 1.5rem;background-color: lightgoldenrodyellow"><h4>' . htmlspecialchars($errstr) . ' [' . $this->lang->translate('Error'). ': ' . $errno . ']</h4><div>' . substr($errfile, $intercept) . ' [' . $this->lang->translate('Line'). ': ' . $errline . ']</div></div>';
            $file = file($errfile);
            echo '<div style="margin-top: 1rem; padding: 10px; border: solid 1px #eee">';
            foreach($file as $key => $val){
                $line = $key + 1;
                if($line < $errline - 10 || $line > $errline + 10){
                    unset($file[$key]);
                }
                else{
                    if($line == $errline){
                        echo '<div style="color: firebrick">[' . $line . '] ' . str_replace(' ', '&nbsp;', htmlspecialchars($val)) . '</div>';
                    }
                    else{
                        echo '<div style="color: grey">[' . $line . '] ' . str_replace(' ', '&nbsp;', htmlspecialchars($val)) . '</div>';
                    }
                }
            }
            echo '</div>';
        }
        ob_end_flush();
        exit();
    }
    private function log($message)
    {
        $errPath = $this->app->rootDir() . DIRECTORY_SEPARATOR . 'assist' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . date('Ym');
        if(!is_dir($errPath)){
            @mkdir($errPath, 0777, true);
        }
        error_log($message, 3, $errPath . DIRECTORY_SEPARATOR . date('d') . '.log');
    }
}