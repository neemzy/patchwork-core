<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;

trait TogglableModel
{
    public static function getActive($active = true)
    {
        return R::find(static::unqualify(), 'active = ? ORDER BY '.static::orderBy(), [+$active]);
    }



    public static function defaultState()
    {
        return false;
    }



    public function toggle($force = null)
    {
        $this->active = ($force === null ? !$this->active : $force);
    }



    protected function togglableUpdate()
    {
        $this->active = $this->id ? !!$this->active : static::defaultState();
    }
}
