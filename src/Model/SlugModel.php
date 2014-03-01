<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;

trait SlugModel
{
    abstract public function slugify();

    public static function findBySlug($slug)
    {
        return R::findOne(static::unqualify(), 'slug = ?', $slug);
    }



    protected function slugUpdate()
    {
        $this->slug = $this->slugify();
    }
}
