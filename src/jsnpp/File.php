<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class File
{
    private $errors = [];
    private $app;
    private $lang;
    private $name = 'file';
    public function __construct(Application $app, Lang $lang)
    {
        $this->app = $app;
        $this->lang = $lang;
    }
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }
    public function extension($extensions, $alert = '')
    {
        if(is_string($extensions)){
            $extensions = Tools::toArrTrim($extensions, ',');
        }
        $temp = explode('.', $_FILES[$this->name]['name']);
        if(!in_array(end($temp), $extensions)){
            if(empty($alert)){
                $alert = $this->lang->translate('Extension is not allowed');
            }
            $this->errors[] = $alert;
        }
        return $this;
    }
    public function type($types, $alert = '')
    {
        if(is_string($types)){
            $types = Tools::toArrTrim($types, ',');
        }
        if(!in_array($_FILES[$this->name]['type'], $types)){
            if(empty($alert)){
                $alert = $this->lang->translate('File type is not allowed');
            }
            $this->errors[] = $alert;
        }
        return $this;
    }
    public function size($size, $alert = '')
    {
        if($_FILES[$this->name]['size'] > $size){
            if(empty($alert)){
                $alert = $this->lang->translate('The uploaded file size exceeds the allowed value');
            }
            $this->errors[] = $alert;
        }
        return $this;
    }
    public function save($path = '', $raw = false)
    {
        if($_FILES[$this->name]['error'] > 0){
            $this->errors[] = $_FILES[$this->name]['error'];
        }
        if(count($this->errors) == 0){
            if(empty($path)){
                $path = 'public' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . date('Ymd');
            }
            else{
                $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
                $path = trim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . date('Ymd');
            }
            $pathAll = $this->app->rootDir() . DIRECTORY_SEPARATOR . $path;
            if(!is_dir($pathAll)){
                @mkdir($pathAll, 0777, true);
            }
            if($raw){
                move_uploaded_file($_FILES[$this->name]['tmp_name'], $pathAll . DIRECTORY_SEPARATOR . $_FILES[$this->name]['name']);
                $file = str_replace(DIRECTORY_SEPARATOR, '/', $path . '/' . $_FILES[$this->name]['name']);
            }
            else{
                $temp = explode('.', $_FILES[$this->name]['name']);
                $extension = end($temp);
                $fileName = md5($_FILES[$this->name]['name'] .time());
                move_uploaded_file($_FILES[$this->name]['tmp_name'], $pathAll . DIRECTORY_SEPARATOR . $fileName . '.' . $extension);
                $file = str_replace(DIRECTORY_SEPARATOR, '/', $path . '/' . $fileName . '.' . $extension);
            }
            return $file;
        }
        else{
            return false;
        }
    }
    public function getError()
    {
        return implode(', ', $this->errors);
    }
    public function clearError()
    {
        $this->errors = [];
    }
}