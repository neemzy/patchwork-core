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
        $clone = App::getInstance()['redbean']->dup($this->bean)->box();

        foreach ($clone->getAsserts() as $key => $assert) {
            $clone->$key = $clone->bean->$key;
        }

        if (static::uses('file')) {
            $this->cloneFilesFor($clone);
        }

        $clone->save();
    }
}
