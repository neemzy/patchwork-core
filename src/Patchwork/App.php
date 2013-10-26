<?php

namespace Patchwork;

use Silex\Application;

class App extends Application
{
    const DEFAULT_NAME = 'default';

    private static $instances = array();



    public static function getInstance($param = self::DEFAULT_NAME)
    {
        if (is_array($param)) {
            $name = array_key_exists('name', $param) ? $param['name'] : self::DEFAULT_NAME;
        } else {
            $name = $param;
            $param = array();
        }

        if (! array_key_exists($name, self::$instances)) {
            self::$instances[$name] = new self($param);
        }

        return self::$instances[$name];
    }
}
