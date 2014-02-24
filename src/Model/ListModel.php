<?php

namespace Patchwork\Model;

trait ListModel
{
    protected function assets()
    {
        return array_merge(
            parent::assets(),
            array(
                'position' => null
            )
        );
    }



    public function getAll()
    {
        return R::findAll($this->getType());
    }



    public function update()
    {
        if ((! $this->position) || ($this->position && count(R::find($this->getType(), 'position = ? AND id != ?', array($this->position, $this->id))))) {
            $position = R::getCell('SELECT position FROM '.$this->getType().' ORDER BY position DESC LIMIT 1');
            $this->position = $position + 1;
        }
    }



    public function delete()
    {
        R::exec('UPDATE '.$this->getType().' SET position = position - 1 WHERE position > ?', array($this->position));
    }
}
