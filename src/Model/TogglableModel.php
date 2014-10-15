<?php

namespace Patchwork\Model;

trait TogglableModel
{
    /**
     * Gets active instances of the model
     *
     * @return array
     */
    public static function getActive($active = true)
    {
        return $this->app['redbean']->find($this->getTableName(), 'active = ? ORDER BY '.static::orderBy(), [+$active]);
    }



    /**
     * Defines the model's default state
     *
     * @return void
     */
    public function defaultState()
    {
        return false;
    }



    /**
     * Toggles this model's state
     *
     * @return void
     */
    public function toggle($force = null)
    {
        $this->active = (null === $force ? !$this->active : $force);
    }



    /**
     * RedBean update method
     * Sets this model to default state if it's just been created
     *
     * @return void
     */
    protected function togglableUpdate()
    {
        $this->active = $this->id ? !!$this->active : $this->defaultState();
    }
}
