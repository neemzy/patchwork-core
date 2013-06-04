<?php

namespace Patchwork\Helper;

class RedBean extends \RedBean_Facade
{
    public static function typeHasField($type, $field)
    {
        $columns = self::getColumns($type);
        return isset($columns[$field]);
    }
}
