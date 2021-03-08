<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Captcha
{
    private $app;
    private $session;
    public function __construct(Application $app, Session $session)
    {
        $this->app = $app;
        $this->session = $session;
    }
    public function generate()
    {
        $width = intval(trim($this->app->getConfig('imagewidth')));
        $height = intval(trim($this->app->getConfig('imageheight')));
        $image = imagecreatetruecolor($width, $height);
        $bgcolor=imagecolorallocate($image,rand(230,255),rand(230,255),rand(230,255));
        imagefill($image,0,0,$bgcolor);
        $captchcode = '';
        $fontsize = intval(trim($this->app->getConfig('fontsize')));
        $quantity = intval(trim($this->app->getConfig('quantity')));
        $data='abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $datalen = strlen($data) - 1;
        $wto = ceil($width / $quantity - $fontsize);
        $hfo = ceil(($height - $fontsize) / 4 + $fontsize);
        $hto = ceil(($height - $fontsize) * 3 / 4 + $fontsize);
        for($i = 0; $i < $quantity; $i++){
            $fontcolor=imagecolorallocate($image,rand(0,120),rand(0,120),rand(0,120));
            $fontcontent=substr($data,rand(0, $datalen),1);
            $captchcode .= $fontcontent;
            $angleabs = $angle = rand(0, 30);
            if(rand(1, 100) % 2 == 0){
                $x = ($i * $width / $quantity) + rand(0, $wto) + $wto * 2 / 3;
            }
            else{
                $x = ($i * $width / $quantity) + rand(0, $wto) - $wto * 2 / 3;
                $angle = - $angle;
            }
            if($x < 0){
                $x = 0;
            }
            if($x > $width - $fontsize - $angleabs){
                $x = $width - $fontsize - $angleabs;
            }
            $y = rand($hfo, $hto);
            imagettftext($image, $fontsize, $angle, $x, $y, $fontcolor, Tools::dirName(__DIR__, 1) . 'font' . DIRECTORY_SEPARATOR . 't1.ttf', $fontcontent);
        }
        $this->session->set('_jsnpp_captcha', $captchcode);
        $interferencepoints = intval(trim($this->app->getConfig('interferencepoints')));
        for($i = 0; $i < $interferencepoints; $i++){
            $pointcolor=imagecolorallocate($image,rand(50,120),rand(50,120),rand(50,120));
            imagesetpixel($image,rand(1, $width),rand(1, $height),$pointcolor);
        }
        $interferencelines = intval(trim($this->app->getConfig('interferencelines')));
        for($i = 0; $i < $interferencelines; $i++){
            $linecolor=imagecolorallocate($image,rand(80,220),rand(80,220),rand(80,220));
            $startx = rand(1, $width);
            $starty = rand(1, $height);
            $endx = rand(1, $width);
            $endy = rand(1, $height);
            $thickness = ceil($fontsize / 5);
            if($thickness > 1){
                $thickness = rand(1, $thickness);
            }
            for($j = 0; $j < $thickness; $j++, $startx++, $starty++, $endx++, $endy++){
                imageline($image, $startx, $starty, $endx, $endy, $linecolor);
            }
        }
        header('content-type:image/png');
        imagepng($image);
        imagedestroy($image);
    }
}