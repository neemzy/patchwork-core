<?php

namespace Patchwork;

use Silex\Application;

class App
{
    const DEFAULT_NAME = 'default';

    private static $instances = [];



    public static function getInstance($param = self::DEFAULT_NAME)
    {
        if (is_array($param)) {
            $name = array_key_exists('name', $param) ? $param['name'] : self::DEFAULT_NAME;
        } else {
            $name = $param;
            $param = [];
        }

        if (! array_key_exists($name, self::$instances)) {
            self::$instances[$name] = new Application($param);
        }

        return self::$instances[$name];
    }
}
