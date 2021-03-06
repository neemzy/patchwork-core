<?php

namespace Neemzy\Patchwork\Model;

trait TimestampModel
{
    /**
     * RedBean update method
     * Fills in the creation and update timestamps
     *
     * @return void
     */
    protected function timestampUpdate()
    {
        $this->updated = date('Y-m-d H:i:s');
        $this->id || $this->created = $this->updated;
    }
}
