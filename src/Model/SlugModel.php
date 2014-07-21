<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;

trait SlugModel
{
    public function slugify()
    {
        return Tools::vulgarize($this->__toString());
    }



    public static function findBySlug($slug)
    {
        return R::findOne(static::unqualify(), 'slug = ?', $slug);
    }



    protected function slugUpdate()
    {
        $this->slug = $this->slugify();
    }
}
