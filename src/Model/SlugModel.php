<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;
use Patchwork\Tools;

trait SlugModel
{
    /**
     * Vulgarizes this bean's string representation
     *
     * @return string Vulgarized string
     */
    public function slugify()
    {
        return Tools::vulgarize($this->__toString());
    }



    /**
     * Finds a bean of the current class by its slug
     *
     * @return Patchwork\Model\AbstractModel Found bean (if any)
     */
    public static function findBySlug($slug)
    {
        return R::findOne(static::unqualify(), 'slug = ?', $slug);
    }



    /**
     * RedBean update method
     * Caches this bean's slug into one of its fields
     *
     * @return void
     */
    protected function slugUpdate()
    {
        $this->slug = $this->slugify();
    }
}
