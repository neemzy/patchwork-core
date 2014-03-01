<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;

trait TimestampModel
{
    protected function timestampUpdate()
    {
        $this->updated = date('Y-m-d H:i:s');
        $this->id || $this->created = $this->updated;
    }
}
