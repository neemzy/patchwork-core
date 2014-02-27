<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;

trait TogglableModel
{
    public function toggle($force = null)
    {
        $this->active = ($force === null ? !$this->active : $force);
        $this->save();
    }



    public function getActive($active = true)
    {
        return R::find(static::unqualify(), 'active = ? ORDER BY '.static::orderBy(), [+$active]);
    }
}
