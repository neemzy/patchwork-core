<?php

namespace Patchwork\Helper;

class RedBean extends \RedBean_Facade
{
    public static function typeHasField($type, $field)
    {
        try {
            $columns = self::getColumns($type);
            return isset($columns[$field]);
        } catch (Exception $e) {
            return false;
        }
    }
}
