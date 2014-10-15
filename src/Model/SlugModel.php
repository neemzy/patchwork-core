<?php

namespace Patchwork\Model;

use Patchwork\Tools;

trait SlugModel
{
    /**
     * Vulgarizes this bean's string representation
     *
     * @return string
     */
    public function slugify()
    {
        return Tools::vulgarize($this->__toString()) ?: $slug = $this->getTableName().'-'.$this->id;
    }



    /**
     * Finds a bean of the current class by its slug
     *
     * @return Patchwork\Model\AbstractModel
     */
    public static function findBySlug($slug)
    {
        return $this->app['redbean']->findOne(static::getTableName(), 'slug = ?', [$slug]);
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
