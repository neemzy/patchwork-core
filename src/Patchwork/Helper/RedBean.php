<?php

namespace Patchwork\Helper;

use Silex\Application;

class RedBean extends \RedBean_Facade
{
    public static $app;



    public static function bindApp(Application $app)
    {
        self::$app = $app;
    }



    public static function typeHasField($type, $field)
    {
        try {
            $columns = self::getColumns($type);
            return isset($columns[$field]);
        } catch (\RedBean_Exception_SQL $e) {
            return false;
        }
    }
}
