<?php

namespace Patchwork\Model;

use Patchwork\App;

trait SlugModel
{
    abstract public function getSlug();

    public static function findBySlug($slug)
    {
        $app = App::getInstance();
        $beans = static::getAll();

        foreach ($beans as $bean) {
            if ($bean->getSlug() == $slug) {
                return $bean;
            }
        }

        return false;
    }
}
