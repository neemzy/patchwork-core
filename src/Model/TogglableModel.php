<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;

trait TogglableModel
{
    /**
     * Gets active instances of the model
     *
     * @return array Instance collection
     */
    public static function getActive($active = true)
    {
        return R::find(static::unqualify(), 'active = ? ORDER BY '.static::orderBy(), [+$active]);
    }



    /**
     * Toggles this bean's state
     *
     * @return void
     */
    public static function defaultState()
    {
        return false;
    }



    /**
     * Toggles this bean's state
     *
     * @return void
     */
    public function toggle($force = null)
    {
        $this->active = ($force === null ? !$this->active : $force);
    }



    /**
     * RedBean update method
     * Sets this bean to default state if it's just been created
     *
     * @return void
     */
    protected function togglableUpdate()
    {
        $this->active = $this->id ? !!$this->active : static::defaultState();
    }
}
