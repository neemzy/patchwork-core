<?php

namespace Patchwork\Model;

abstract class BaseModel extends AbstractModel
{
    use ListModel, TogglableModel, ImageModel {
        ListModel::update as listUpdate;
        ListModel::delete as listDelete;
        ImageModel::update as imageUpdate;
        ImageModel::delete as imageDelete;
    }



    public function update()
    {
        $this->listUpdate();
        $this->imageUpdate();

        parent::update();
    }



    public function delete()
    {
        $this->listDelete();
        $this->imageDelete();

        parent::delete();
    }
}
