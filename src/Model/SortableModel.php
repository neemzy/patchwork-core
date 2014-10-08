<?php

namespace Patchwork\Model;

use Patchwork\App;

trait SortableModel
{
    /**
     * Moves this bean up or down by position
     *
     * @param bool $up Move way
     *
     * @return void
     */
    public function move($up)
    {
        $app = App::getInstance();
        $class = static::unqualify();

        if ($up && ($this->position > 1)) {
            $this->position--;
            $app['redbean']->exec('UPDATE '.$class.' SET position = position + 1 WHERE position = ?', [$this->position]);
        } else if ((! $up) && ($this->position < $app['redbean']->count($class))) {
            $this->position++;
            $app['redbean']->exec('UPDATE '.$class.' SET position = position - 1 WHERE position = ?', [$this->position]);
        }

        $app['redbean']->store($this);
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
        $app = App::getInstance();

        if (!$this->position || ($this->position && count($app['redbean']->find(static::unqualify(), 'position = ? AND id != ?', [$this->position, $this->id])))) {
            $position = $app['redbean']->getCell('SELECT position FROM '.static::unqualify().' ORDER BY position DESC LIMIT 1');
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
        App::getInstance()['redbean']->exec('UPDATE '.static::unqualify().' SET position = position - 1 WHERE position > ?', [$this->position]);
    }
}
