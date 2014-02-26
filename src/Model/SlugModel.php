<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;

trait SlugModel
{
    abstract public function getSlug();

    public static function findBySlug($slug)
    {
        $app = App::getInstance();
        $ranges = self::getAll();

        foreach ($ranges as $range) {
            if ($range->getSlug() == $slug) {
                return $range;
            }
        }

        return false;
    }
}
