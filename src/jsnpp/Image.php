<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Image
{
    public function convert($from, $type = 'png', $to = '', $quality = 80)
    {
        if(empty($type)){
            $type = 'png';
        }
        $ext = pathinfo($from, PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        if($ext == 'png'){
            $im = imagecreatefrompng($from);
        }
        elseif($ext == 'gif'){
            $im = imagecreatefromgif($from);
        }
        elseif($ext == 'webp'){
            $im = imagecreatefromwebp($from);
        }
        else{
            $im = imagecreatefromjpeg($from);
        }
        imagepalettetotruecolor($im);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        if(empty($to)){
            $to = $from;
        }
        $toname = pathinfo($to, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($to, PATHINFO_FILENAME);
        $type = strtolower($type);
        if($type == 'png'){
            $to = $toname . '.png';
            imagepng($im, $to, floor($quality * 9 / 100));
        }
        elseif($type == 'gif'){
            $to = $toname . '.gif';
            imagegif($im, $to);
        }
        elseif($type == 'webp'){
            $to = $toname . '.webp';
            imagewebp($im, $to, $quality);
        }
        else{
            if($type == 'jpg' || $type == 'jpeg'){
                $to = $toname . '.jpg';
            }
            imagejpeg($im, $to, $quality);
        }
        imagedestroy($im);
    }
    public function resize($width, $height, $from, $to = '', $quality = 80)
    {
        $ext = pathinfo($from, PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        if($ext == 'png'){
            $im = imagecreatefrompng($from);
        }
        elseif($ext == 'gif'){
            $im = imagecreatefromgif($from);
        }
        elseif($ext == 'webp'){
            $im = imagecreatefromwebp($from);
        }
        else{
            $im = imagecreatefromjpeg($from);
        }
        $x = imagesx($im);
        $y = imagesy($im);
        if($width == $x && $height == $y && empty($to)){
            imagedestroy($im);
        }
        else{
            $im2 = imagecreatetruecolor($width, $height);
            imagealphablending($im2, false);
            imagesavealpha($im2, true);
            imagecopyresampled($im2, $im, 0, 0, 0, 0, floor($width), floor($height), $x, $y);
            if(empty($to)){
                $to = $from;
            }
            $ext = pathinfo($to, PATHINFO_EXTENSION);
            $ext = strtolower($ext);
            if($ext == 'png'){
                imagepng($im2, $to, floor($quality * 9 / 100));
            }
            elseif($ext == 'gif'){
                imagegif($im2, $to);
            }
            elseif($ext == 'webp'){
                imagewebp($im2, $to, $quality);
            }
            else{
                imagejpeg($im2, $to, $quality);
            }
            imagedestroy($im2);
            imagedestroy($im);
        }
    }
    public function cut($width, $height, $from, $to = '', $position = 'center', $quality = 80)
    {
        $ext = pathinfo($from, PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        if($ext == 'png'){
            $im = imagecreatefrompng($from);
        }
        elseif($ext == 'gif'){
            $im = imagecreatefromgif($from);
        }
        elseif($ext == 'webp'){
            $im = imagecreatefromwebp($from);
        }
        else{
            $im = imagecreatefromjpeg($from);
        }
        $x = imagesx($im);
        $y = imagesy($im);
        if($width == 0 && $height == 0){
            $width = $x;
            $height = $y;
        }
        elseif($width == 0){
            $width = floor($x * $height / $y);
        }
        elseif($height == 0){
            $height = floor($y * $width / $x);
        }
        $position = strtolower($position);
        if((($width == $x && $height == $y) || ($position == 'adaptraw' && $width > $x && $height > $y)) && empty($to)){
            imagedestroy($im);
        }
        else{
            $src_x = 0;
            $src_y = 0;
            $dst_w = $width;
            $dst_h = $height;
            if($position == 'adapt' || $position == 'adaptraw'){
                $xratio = $x / $width;
                $yratio = $y / $height;
                if($xratio > $yratio){
                    $src_x = floor(($x - $width * $y / $height) / 2);
                    $src_w = $width * $y / $height;
                    $src_h = $y;
                }
                else{
                    $src_y = floor(($y - $height * $x / $width) / 2);
                    $src_w = $x;
                    $src_h = $height * $x / $width;
                }
                $dst_w = floor($dst_w);
                $dst_h = floor($dst_h);
                if($position == 'adaptraw' && $width > $x && $height > $y){
                    $src_x = 0;
                    $src_y = 0;
                    $dst_w = $x;
                    $dst_h = $y;
                    $src_w = $x;
                    $src_h = $y;
                    $width = $x;
                    $height = $y;
                }
            }
            else{
                if($width < $x){
                    switch(strtolower($position)){
                        case 'lefttop':
                        case 'leftcenter':
                        case 'leftbottom':
                            $src_x = 0;
                            break;
                        case 'topcenter':
                        case 'center':
                        case 'bottomcenter':
                            $src_x = floor(($x - $width) / 2);
                            break;
                        case 'righttop':
                        case 'rightcenter':
                        case 'rightbottom':
                            $src_x = floor($x - $width);
                            break;
                    }
                }
                else{
                    $dst_w = $x;
                }
                if($height < $y){
                    switch(strtolower($position)){
                        case 'lefttop':
                        case 'topcenter':
                        case 'righttop':
                            $src_y = 0;
                            break;
                        case 'leftcenter':
                        case 'center':
                        case 'rightcenter':
                            $src_y = floor(($y - $height) / 2);
                            break;
                        case 'leftbottom':
                        case 'bottomcenter':
                        case 'rightbottom':
                            $src_y = floor($y - $height);
                            break;
                    }
                }
                else{
                    $dst_h = $y;
                }
                $dst_w = floor($dst_w);
                $dst_h = floor($dst_h);
                $src_w = $dst_w;
                $src_h = $dst_h;
            }
            $im2 = imagecreatetruecolor($width, $height);
            imagealphablending($im2, false);
            imagesavealpha($im2, true);
            imagecopyresampled($im2, $im, 0, 0, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
            if(empty($to)){
                $to = $from;
            }
            $ext = pathinfo($to, PATHINFO_EXTENSION);
            $ext = strtolower($ext);
            if($ext == 'png'){
                imagepng($im2, $to, floor($quality * 9 / 100));
            }
            elseif($ext == 'gif'){
                imagegif($im2, $to);
            }
            elseif($ext == 'webp'){
                imagewebp($im2, $to, $quality);
            }
            else{
                imagejpeg($im2, $to, $quality);
            }
            imagedestroy($im2);
            imagedestroy($im);
        }
    }
    public function watermark($img, $stamp, $size = '30', $position = 'center', $to = '', $quality = 80)
    {
        if(empty($position)){
            $position = 'center';
        }
        $ext = pathinfo($img, PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        if($ext == 'png'){
            $im = imagecreatefrompng($img);
        }
        elseif($ext == 'gif'){
            $im = imagecreatefromgif($img);
        }
        elseif($ext == 'webp'){
            $im = imagecreatefromwebp($img);
        }
        else{
            $im = imagecreatefromjpeg($img);
        }
        $x = imagesx($im);
        $y = imagesy($im);
        $position = strtolower($position);
        if(preg_match('/(\w+\/)+\w+\.\w{3,4}$/', str_replace('\\', '/', $stamp)) && is_file($stamp)){
            $sext = pathinfo($stamp, PATHINFO_EXTENSION);
            $sext = strtolower($sext);
            if($sext == 'png'){
                $im2 = imagecreatefrompng($stamp);
            }
            elseif($sext == 'gif'){
                $im2 = imagecreatefromgif($stamp);
            }
            elseif($sext == 'webp'){
                $im2 = imagecreatefromwebp($stamp);
            }
            else{
                $im2 = imagecreatefromjpeg($stamp);
            }
            $sx = imagesx($im2);
            $sy = imagesy($im2);
            $dst_x = 0;
            $dst_y = 0;
            if($sx < $x){
                switch(strtolower($position)){
                    case 'lefttop':
                    case 'leftcenter':
                    case 'leftbottom':
                        $dst_x = 10;
                        break;
                    case 'topcenter':
                    case 'center':
                    case 'bottomcenter':
                        $dst_x = floor(($x - $sx) / 2);
                        break;
                    case 'righttop':
                    case 'rightcenter':
                    case 'rightbottom':
                        $dst_x = floor($x - $sx) - 10;
                        break;
                }
            }
            if($sy < $y){
                switch(strtolower($position)){
                    case 'lefttop':
                    case 'topcenter':
                    case 'righttop':
                        $dst_y = 10;
                        break;
                    case 'leftcenter':
                    case 'center':
                    case 'rightcenter':
                        $dst_y = floor(($y - $sy) / 2);
                        break;
                    case 'leftbottom':
                    case 'bottomcenter':
                    case 'rightbottom':
                        $dst_y = floor($y - $sy) - 10;
                        break;
                }
            }
            imagecopy($im, $im2, $dst_x, $dst_y, 0, 0, $sx, $sy);
            if(empty($to)){
                $to = $img;
            }
            if($ext == 'png'){
                imagepng($im, $to, floor($quality * 9 / 100));
            }
            elseif($ext == 'gif'){
                imagegif($im, $to);
            }
            elseif($ext == 'webp'){
                imagewebp($im, $to, $quality);
            }
            else{
                imagejpeg($im, $to, $quality);
            }
            imagedestroy($im2);
            imagedestroy($im);
        }
        else{
            if(preg_match('/^\w+$/', $stamp)){
                $width = $size * mb_strlen($stamp) * 1.2;
                $height = $size * 1.4;
                $hy = $size * 1.1;
            }
            else{
                $width = $size * mb_strlen($stamp) * 1.4;
                $height = $size * 1.4;
                $hy = $size * 1.2;
            }
            $im2 = imagecreatetruecolor($width, $height);
            imagefilledrectangle($im2, 0, 0, $width, $height, 0xFFFFFF);
            imagecolortransparent($im2, imagecolorallocate($im2, 255, 255, 255));
            imagettftext($im2, $size, 0, 0, $hy, 0x000000, Tools::dirName(__DIR__, 1) . 'font' . DIRECTORY_SEPARATOR . 'OPPOSans-H.ttf', $stamp);
            $dst_x = 0;
            $dst_y = 0;
            if($width < $x){
                switch(strtolower($position)){
                    case 'lefttop':
                    case 'leftcenter':
                    case 'leftbottom':
                        $dst_x = 10;
                        break;
                    case 'topcenter':
                    case 'center':
                    case 'bottomcenter':
                        $dst_x = floor(($x - $width) / 2);
                        break;
                    case 'righttop':
                    case 'rightcenter':
                    case 'rightbottom':
                        $dst_x = floor($x - $width) - 10;
                        break;
                }
            }
            if($height < $y){
                switch(strtolower($position)){
                    case 'lefttop':
                    case 'topcenter':
                    case 'righttop':
                        $dst_y = 10;
                        break;
                    case 'leftcenter':
                    case 'center':
                    case 'rightcenter':
                        $dst_y = floor(($y - $height) / 2);
                        break;
                    case 'leftbottom':
                    case 'bottomcenter':
                    case 'rightbottom':
                        $dst_y = floor($y - $height) - 10;
                        break;
                }
            }
            imagecopymerge($im, $im2, $dst_x, $dst_y, 0, 0, $width, $height, 30);
            if(empty($to)){
                $to = $img;
            }
            if($ext == 'png'){
                imagepng($im, $to, floor($quality * 9 / 100));
            }
            elseif($ext == 'gif'){
                imagegif($im, $to);
            }
            elseif($ext == 'webp'){
                imagewebp($im, $to, $quality);
            }
            else{
                imagejpeg($im, $to, $quality);
            }
            imagedestroy($im2);
            imagedestroy($im);
        }
    }
}