<?php

namespace Neemzy\Patchwork\Model;

trait SortableModel
{
    /**
     * Moves this model up or down in position
     *
     * @param bool $up Move way
     *
     * @return void
     */
    public function move($up)
    {
        $table = $this->getTableName();

        if ($up && ($this->position > 1)) {
            $this->position--;
            $this->app['redbean']->exec('UPDATE '.$table.' SET position = position + 1 WHERE position = ?', [$this->position]);
        } else if ((! $up) && ($this->position < $this->app['redbean']->count($table))) {
            $this->position++;
            $this->app['redbean']->exec('UPDATE '.$table.' SET position = position - 1 WHERE position = ?', [$this->position]);
        }

        $this->app['redbean']->store($this);
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
        $table = $this->getTableName();

        if (!$this->position || ($this->position && count($this->app['redbean']->find($table, 'position = ? AND id != ?', [$this->position, $this->id])))) {
            $position = $this->app['redbean']->getCell('SELECT position FROM '.$table.' ORDER BY position DESC LIMIT 1');
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
        $this->app['redbean']->exec('UPDATE '.$this->getTableName().' SET position = position - 1 WHERE position > ?', [$this->position]);
    }
}
