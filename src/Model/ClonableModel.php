<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;

trait ClonableModel
{
    /**
     * Duplicate this bean
     *
     * @return void
     */
    public function dup()
    {
        $clone = R::dup($this->bean);
        $clone->save();

        if (static::uses('file')) {
            $this->cloneFilesFor($clone);
        }
    }
}
