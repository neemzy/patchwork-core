<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;

trait SortableModel
{
    public function move($up)
    {
        $class = static::unqualify();

        if ($up && ($this->position > 1)) {
            $this->position--;
            R::exec('UPDATE '.$class.' SET position = position + 1 WHERE position = ?', [$this->position]);
        } else if ((! $up) && ($this->position < R::count($class))) {
            $this->position++;
            R::exec('UPDATE '.$class.' SET position = position - 1 WHERE position = ?', [$this->position]);
        }

        $this->save();
    }



    public static function orderBy()
    {
        return 'position ASC';
    }



    protected function sortableUpdate()
    {
        if ((! $this->position) || ($this->position && count(R::find(static::unqualify(), 'position = ? AND id != ?', [$this->position, $this->id])))) {
            $position = R::getCell('SELECT position FROM '.static::unqualify().' ORDER BY position DESC LIMIT 1');
            $this->position = $position + 1;
        }
    }

    protected function sortableDelete()
    {
        R::exec('UPDATE '.static::unqualify().' SET position = position - 1 WHERE position > ?', [$this->position]);
    }
}
