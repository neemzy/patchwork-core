<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;

trait TogglableModel
{
    public static function getActive($active = true)
    {
        return R::find(static::unqualify(), 'active = ? ORDER BY '.static::orderBy(), [+$active]);
    }



    public static function getDefaultState()
    {
        return false;
    }



    public function toggle($force = null, $persist = true)
    {
        $this->active = ($force === null ? !$this->active : $force);

        $persist && $this->save();
    }



    public function dispense()
    {
        $this->toggle(static::getDefaultState(), false);
    }
}
