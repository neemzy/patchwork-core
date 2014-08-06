<?php

namespace Patchwork\Model;

use \RedBean_Facade as R;

trait SortableModel
{
    /**
     * Moves this bean up or down by position
     *
     * @param $up bool Move way
     *
     * @return void
     */
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



    /**
     * Overrideable method to get the default sorting index and way
     *
     * @return string SQL snippet
     */
    public static function orderBy()
    {
        return 'position ASC';
    }



    /**
     * RedBean update method
     * Automates position assigning
     *
     * @return void
     */
    protected function sortableUpdate()
    {
        if ((! $this->position) || ($this->position && count(R::find(static::unqualify(), 'position = ? AND id != ?', [$this->position, $this->id])))) {
            $position = R::getCell('SELECT position FROM '.static::unqualify().' ORDER BY position DESC LIMIT 1');
            $this->position = $position + 1;
        }
    }



    /**
     * RedBean deletion method
     * Regulates the siblings' positions
     *
     * @return void
     */
    protected function sortableDelete()
    {
        R::exec('UPDATE '.static::unqualify().' SET position = position - 1 WHERE position > ?', [$this->position]);
    }
}
