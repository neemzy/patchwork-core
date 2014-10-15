<?php

namespace Patchwork\Model;

use Patchwork\Tools;

trait SlugModel
{
    /**
     * Finds a model by its slug
     *
     * @return Patchwork\Model\AbstractModel
     */
    public static function findBySlug($slug)
    {
        return $this->app['redbean']->findOne(static::getTableName(), 'slug = ?', [$slug]);
    }



    /**
     * Generates a slug for the model
     *
     * @return string
     */
    public function slugify()
    {
        return Tools::vulgarize($this->__toString()) ?: $slug = $this->getTableName().'-'.$this->id;
    }



    /**
     * RedBean update method
     * Caches this model's slug into one of its fields
     *
     * @return void
     */
    protected function slugUpdate()
    {
        $this->slug = $this->slugify();
    }
}
