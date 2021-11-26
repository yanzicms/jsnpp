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
 * @property Db db
 * @property Event event
 * @property Img img
 * @property Output output
 * @property Upload upload
 */
class Config extends Connector
{
    /**
     * @return Config
     */
    public function setConfig($name, $value = '')
    {
        $this->set('execSetConfig', $name, $value);
        return $this;
    }
    protected function execSetConfig($name, $value)
    {
        $this->app->setConfig($name, $value);
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    /**
     * @return Config
     */
    public function writeConfig($name, $value = '')
    {
        $this->set('execWriteConfig', $name, $value);
        return $this;
    }
    protected function execWriteConfig($name, $value)
    {
        $this->app->writeConfig($name, $value);
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    /**
     * @return Config
     */
    public function writeCustomize($customize, $name, $value = '')
    {
        $this->set('execWriteCustomize', $customize, $name, $value);
        return $this;
    }
    protected function execWriteCustomize($customize, $name, $value)
    {
        $this->app->writeCustomize($customize, $name, $value);
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
    /**
     * @return Config
     */
    public function writeRouting($name, $value = '')
    {
        $this->set('execWriteRouting', $name, $value);
        return $this;
    }
    protected function execWriteRouting($name, $value)
    {
        $this->app->writeRouting($name, $value);
        return [
            'result' => true,
            'code' => 0,
            'message' => 'ok'
        ];
    }
}