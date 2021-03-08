<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

class Cache
{
    private $app;
    private $cache;
    private $tag;
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->cache = 'jsnpp\cache\\' . ucfirst(strtolower(trim($this->app->getConfig('cache'))));
    }
    public function get($key, $default = null)
    {
        return $this->app->get($this->cache)->get($key, $default);
    }
    public function set($key, $value, $ttl = null)
    {
        $this->app->get($this->cache)->set($key, $value, $ttl, $this->tag);
        return $this;
    }
    public function tag($tag = '')
    {
        $this->tag = trim($tag);
        return $this;
    }
    public function deleteTag($tag)
    {
        $this->app->get($this->cache)->deleteTag($tag);
        return $this;
    }
    public function delete($key)
    {
        $this->app->get($this->cache)->delete($key);
        return $this;
    }
    public function clear()
    {
        $this->app->get($this->cache)->clear();
        return $this;
    }
    public function getMultiple($keys, $default = null)
    {
        return $this->app->get($this->cache)->getMultiple($keys, $default);
    }
    public function setMultiple($values, $ttl = null)
    {
        $this->app->get($this->cache)->setMultiple($values, $ttl);
        return $this;
    }
    public function deleteMultiple($keys)
    {
        $this->app->get($this->cache)->deleteMultiple($keys);
        return $this;
    }
    public function has($key)
    {
        return $this->app->get($this->cache)->has($key);
    }
}