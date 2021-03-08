<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Upload extends Connector
{
    private $file;
    private $lang;
    private $fileName;
    public function initialize(){
        $this->file = $this->app->get('file');
        $this->lang = $this->app->get('lang');
    }
    public function save($path = '', $raw = false)
    {
        $this->set('execSave', $path, $raw);
        return $this;
    }
    protected function execSave($path, $raw)
    {
        $result = true;
        $list = [];
        $message = 'ok';
        $re = $this->file->save($path, $raw);
        if($re === false){
            $result = false;
            $list[] = $message = $this->file->getError();
        }
        else{
            $this->fileName = $re;
        }
        return [
            'result' => $result,
            'code' => 0,
            'message' => $message,
            'list' => $list
        ];
    }
    public function box($name)
    {
        $this->set('execBox', $name);
        return $this;
    }
    protected function execBox($name)
    {
        $this->box->set($name, $this->fileName);
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    public function check($name, $expression, $alert = null)
    {
        $this->set('execCheck', $name, $expression, $alert);
        return $this;
    }
    protected function execCheck($name, $expression, $alert)
    {
        $name = trim($name);
        $result = true;
        $list = [];
        $message = 'ok';
        switch($name){
            case 'extension':
            case 'ext':
                $this->file->clearError();
                $this->file->extension($expression, $alert);
                $err = $this->file->getError();
                if(!empty($err)){
                    $result = false;
                    $list[] = $message = $err;
                }
                break;
            case 'type':
                $this->file->clearError();
                $this->file->type($expression, $alert);
                $err = $this->file->getError();
                if(!empty($err)){
                    $result = false;
                    $list[] = $message = $err;
                }
                break;
            case 'size':
                $this->file->clearError();
                $this->file->size($expression, $alert);
                $err = $this->file->getError();
                if(!empty($err)){
                    $result = false;
                    $list[] = $message = $err;
                }
                break;
            default:
                $result = false;
                $list[] = $message = $this->lang->translate('Can only check: extension, type, size');
                break;
        }
        return [
            'result' => $result,
            'code' => 0,
            'message' => $message,
            'list' => $list
        ];
    }
    public function setName($name)
    {
        $this->set('execSetName', $name);
        return $this;
    }
    protected function execSetName($name)
    {
        $this->file->name($name);
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
}