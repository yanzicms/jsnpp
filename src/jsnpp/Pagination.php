<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Pagination
{
    protected $app;
    public function __construct(Application $app){
        $this->app = $app;
    }
    public function getHtml($page, $pages, $pagingUrl)
    {
        $dbtype = $this->app->getConfig('pagination');
        $dbtype = 'jsnpp\pagination\\' . ucfirst(strtolower(trim($dbtype)));
        $numbers = $this->app->getConfig('numbers');
        $previous = $this->app->getConfig('previous');
        $next = $this->app->getConfig('next');
        $containsul = $this->app->getConfig('containsul');
        $pagingArr = $this->getArr($page, $pages, $pagingUrl, $numbers, $previous, $next);
        return $this->app->get($dbtype)->getHtml($pagingArr, $containsul);
    }
    public function getSimpleHtml($page, $pages, $pagingUrl)
    {
        $dbtype = $this->app->getConfig('pagination');
        $dbtype = 'jsnpp\pagination\\' . ucfirst(strtolower(trim($dbtype)));
        $previous = $this->app->getConfig('previous');
        $next = $this->app->getConfig('next');
        $containsul = $this->app->getConfig('containsul');
        $simpleArr = $this->getSimpleArr($page, $pages, $pagingUrl, $previous, $next);
        return $this->app->get($dbtype)->getHtml($simpleArr, $containsul);
    }
    private function getSimpleArr($page, $pages, $pagingUrl, $previous, $next)
    {
        $parr = [];
        for($i = 0; $i <= $pages + 1; $i ++){
            if($i == 0){
                $parr[] = [
                    'page' => $previous,
                    'url' => ($page == 1) ? '#' : $pagingUrl . ($page - 1),
                    'disabled' => ($page == 1) ? 'disabled' : '',
                    'active' => ''
                ];
                continue;
            }
            elseif($i > $pages){
                $parr[] = [
                    'page' => $next,
                    'url' => ($page == $pages) ? '#' : $pagingUrl . ($page + 1),
                    'disabled' => ($page == $pages) ? 'disabled' : '',
                    'active' => ''
                ];
                continue;
            }
            elseif($i == $page){
                $parr[] = [
                    'page' => $i,
                    'url' => $pagingUrl . $i,
                    'disabled' => '',
                    'active' => 'active'
                ];
                continue;
            }
            else{
                continue;
            }
        }
        return $parr;
    }
    private function getArr($page, $pages, $pagingUrl, $numbers, $previous, $next)
    {
        $parr = [];
        $left = false;
        $right = false;
        for($i = 0; $i <= $pages + 1; $i ++){
            if($i == 0){
                $parr[] = [
                    'page' => $previous,
                    'url' => ($page == 1) ? '#' : $pagingUrl . ($page - 1),
                    'disabled' => ($page == 1) ? 'disabled' : '',
                    'active' => ''
                ];
                continue;
            }
            if($i > $pages){
                $parr[] = [
                    'page' => $next,
                    'url' => ($page == $pages) ? '#' : $pagingUrl . ($page + 1),
                    'disabled' => ($page == $pages) ? 'disabled' : '',
                    'active' => ''
                ];
                continue;
            }
            if($i == 1){
                $parr[] = [
                    'page' => $i,
                    'url' => $pagingUrl . $i,
                    'disabled' => '',
                    'active' => ($page == $i) ? 'active' : ''
                ];
                continue;
            }
            if($i == $pages){
                $parr[] = [
                    'page' => $i,
                    'url' => $pagingUrl . $i,
                    'disabled' => '',
                    'active' => ($page == $i) ? 'active' : ''
                ];
                continue;
            }
            if($i > 1 && $i < $page - $numbers && $left == false){
                $parr[] = [
                    'page' => '...',
                    'url' => '#',
                    'disabled' => 'disabled',
                    'active' => ''
                ];
                $left = true;
                continue;
            }
            if($i < $pages && $i > $page + $numbers && $right == false){
                $parr[] = [
                    'page' => '...',
                    'url' => '#',
                    'disabled' => 'disabled',
                    'active' => ''
                ];
                $right = true;
                continue;
            }
            if($i >= $page - $numbers && $i <= $page + $numbers){
                $parr[] = [
                    'page' => $i,
                    'url' => $pagingUrl . $i,
                    'disabled' => '',
                    'active' => ($page == $i) ? 'active' : ''
                ];
                continue;
            }
        }
        return $parr;
    }
}