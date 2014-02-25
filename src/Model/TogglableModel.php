<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;

trait TogglableModel
{
    public function toggle($force = null)
    {
        $this->active = ($force === null ? !$this->active : $force);

        R::store($this);
    }
}
