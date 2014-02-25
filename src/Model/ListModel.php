<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;

trait ListModel
{
    public function move($up)
    {
        $class = $this->getType();

        if ($up && ($this->position > 1)) {
            $this->position--;
            R::exec('UPDATE '.$class.' SET position = position + 1 WHERE position = ?', array($this->position));
        } else if ((! $up) && ($this->position < R::count($class))) {
            $this->position++;
            R::exec('UPDATE '.$class.' SET position = position - 1 WHERE position = ?', array($this->position));
        }

        R::store($this);
    }



    public function getAll()
    {
        return R::findAll($this->getType(), 'ORDER BY position ASC');
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
