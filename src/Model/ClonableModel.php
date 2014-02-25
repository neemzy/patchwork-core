<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;

trait ClonableModel
{
    public function dup()
    {
        $clone = R::dup($this->bean);
        R::store($clone);

        if (in_array(__NAMESPACE__.'\ImageModel', class_uses(__CLASS__))) {
            $this->cloneImageFor($clone);
        }
    }
}
