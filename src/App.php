<?php

namespace Patchwork;

use Silex\Application;

class App
{
    /**
     * @var string Default instance name
     */
    const DEFAULT_NAME = 'default';

    /**
     * @var array Instance collection
     */
    private static $instances = [];



    /**
     * Gets an instance by its name
     *
     * @param $param string|array Requested instance name or parameter array
     *
     * @return App Requested instance, created on-the-fly if it doesn't exist
     */
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
