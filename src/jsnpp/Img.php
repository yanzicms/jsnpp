<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Img extends Connector
{
    private $image;
    public function initialize(){
        $this->image = $this->app->get('image');
    }
    public function resize($width, $height, $oimg, $nimg = '', $quality = 80)
    {
        $this->set('execResize', $width, $height, $oimg, $nimg, $quality);
        return $this;
    }
    protected function execResize($width, $height, $oimg, $nimg, $quality)
    {
        if(is_string($width) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $width, $metchs)){
            $width = $this->findBoxValue($metchs[1]);
        }
        if(is_string($height) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $height, $metchs)){
            $height = $this->findBoxValue($metchs[1]);
        }
        if(preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $oimg, $metchs)){
            $oimg = $this->findBoxValue($metchs[1]);
        }
        if(!empty($nimg) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $nimg, $metchs)){
            $nimg = $this->findBoxValue($metchs[1]);
        }
        $this->image->resize($width, $height, $oimg, $nimg, $quality);
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    public function cut($width, $height, $oimg, $nimg = '', $position = 'center', $quality = 80)
    {
        $this->set('execCut', $width, $height, $oimg, $nimg, $position, $quality);
        return $this;
    }
    protected function execCut($width, $height, $oimg, $nimg, $position, $quality)
    {
        if(is_string($width) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $width, $metchs)){
            $width = $this->findBoxValue($metchs[1]);
        }
        if(is_string($height) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $height, $metchs)){
            $height = $this->findBoxValue($metchs[1]);
        }
        if(preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $oimg, $metchs)){
            $oimg = $this->findBoxValue($metchs[1]);
        }
        if(!empty($nimg) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $nimg, $metchs)){
            $nimg = $this->findBoxValue($metchs[1]);
        }
        $this->image->cut($width, $height, $oimg, $nimg, $position, $quality);
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    public function watermark($img, $stamp, $size = '30', $position = 'center', $to = '', $quality = 80)
    {
        $this->set('execWatermark', $img, $stamp, $size, $position, $to, $quality);
        return $this;
    }
    protected function execWatermark($img, $stamp, $size, $position, $to, $quality)
    {
        if(is_string($img) && preg_match('/^:box *\( *([A-Za-z][A-Za-z0-9_\.]*) *\)$/i', $img, $metchs)){
            $img = $this->findBoxValue($metchs[1]);
        }
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
        $this->image->watermark($img, $stamp, $size, $position, $to, $quality);
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