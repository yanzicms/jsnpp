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
 * @property Check check
 * @property Config config
 * @property Db db
 * @property Event event
 * @property Img img
 * @property Output output
 */
class Upload extends Connector
{
    private $file;
    private $lang;
    private $fileName;
    private $image;
    public function initialize(){
        $this->file = $this->app->get('file');
        $this->lang = $this->app->get('lang');
        $this->image = $this->app->get('image');
    }
    /**
     * @return Upload
     */
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
    /**
     * @return Upload
     */
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
    /**
     * @return Upload
     */
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
    /**
     * @return Upload
     */
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
    /**
     * @return Upload
     */
    public function resize($width, $height)
    {
        $this->set('execResize', $width, $height);
        return $this;
    }
    protected function execResize($width, $height)
    {
        if(!empty($this->fileName)){
            if(is_string($width) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $width, $metchs)){
                $width = $this->findBoxValue($metchs[1]);
            }
            if(is_string($height) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $height, $metchs)){
                $height = $this->findBoxValue($metchs[1]);
            }
            $this->image->resize($width, $height, $this->app->rootDir() . DIRECTORY_SEPARATOR . $this->fileName);
        }
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    /**
     * @return Upload
     */
    public function cut($width, $height, $nimg = '', $position = 'center', $quality = 80)
    {
        $this->set('execCut', $width, $height, $nimg, $position, $quality);
        return $this;
    }
    protected function execCut($width, $height, $nimg, $position, $quality)
    {
        if(!empty($this->fileName)){
            if(is_string($width) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $width, $metchs)){
                $width = $this->findBoxValue($metchs[1]);
            }
            if(is_string($height) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $height, $metchs)){
                $height = $this->findBoxValue($metchs[1]);
            }
            if(!empty($nimg) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $nimg, $metchs)){
                $nimg = $this->findBoxValue($metchs[1]);
            }
            $this->image->cut($width, $height, $this->app->rootDir() . DIRECTORY_SEPARATOR . $this->fileName, $nimg, $position, $quality);
        }
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    /**
     * @return Upload
     */
    public function watermark($stamp, $size = '30', $position = 'center', $to = '', $quality = 80)
    {
        $this->set('execWatermark', $stamp, $size, $position, $to, $quality);
        return $this;
    }
    protected function execWatermark($stamp, $size, $position, $to, $quality)
    {
        if(!empty($this->fileName)){
            if(is_string($stamp) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $stamp, $metchs)){
                $stamp = $this->findBoxValue($metchs[1]);
            }
            if(!empty($size) && is_string($size) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $size, $metchs)){
                $size = $this->findBoxValue($metchs[1]);
            }
            if(is_string($position) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $position, $metchs)){
                $position = $this->findBoxValue($metchs[1]);
            }
            if(!empty($to) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $to, $metchs)){
                $to = $this->findBoxValue($metchs[1]);
            }
            $this->image->watermark($this->app->rootDir() . DIRECTORY_SEPARATOR . $this->fileName, $stamp, $size, $position, $to, $quality);
        }
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    private function findBoxValue($mstr)
    {
        $commandArr = explode('.', $mstr);
        $value = array_shift($commandArr);
        $value = $this->box->get($value);
        if(count($commandArr) > 0){
            foreach($commandArr as $val){
                if(isset($value[$val])){
                    $value = $value[$val];
                }
                else{
                    $value = null;
                    break;
                }
            }
        }
        return $value;
    }
}