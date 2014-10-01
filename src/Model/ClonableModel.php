<?php

namespace Patchwork\Model;

use Patchwork\App;

trait ClonableModel
{
    /**
     * Duplicate this bean
     *
     * @return void
     */
    public function dup()
    {
        $clone = App::getInstance()['redbean']->dup($this->bean);
        $clone->save();

        if (static::uses('file')) {
            $this->cloneFilesFor($clone);
        }
    }
}
