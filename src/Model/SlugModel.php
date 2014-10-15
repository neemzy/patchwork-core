<?php

namespace Neemzy\Patchwork\Model;

trait SlugModel
{
    /**
     * Generates a slug for the model
     *
     * @return string
     */
    public function slugify()
    {
        return $this->app['tools']->vulgarize($this->__toString()) ?: $slug = $this->getTableName().'-'.$this->id;
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
