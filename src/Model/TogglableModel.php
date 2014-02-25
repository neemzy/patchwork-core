<?php

namespace Patchwork\Model;

use Patchwork\Helper\RedBean as R;

trait TogglableModel
{
    public function toggle($force = null)
    {
        $this->active = ($force === null ? !$this->active : $force);

        R::store($this);
    }
}
