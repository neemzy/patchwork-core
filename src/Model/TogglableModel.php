<?php

namespace Neemzy\Patchwork\Model;

trait TogglableModel
{
    /**
     * Toggles this model's state
     *
     * @return void
     */
    public function toggle($force = null)
    {
        $this->active = (null === $force ? !$this->active : $force);
    }



    /**
     * Defines the model's default state
     *
     * @return void
     */
    protected function getDefaultState()
    {
        return false;
    }



    /**
     * RedBean update method
     * Sets this model to default state if it's just been created
     *
     * @return void
     */
    protected function togglableUpdate()
    {
        $this->active = $this->id ? !!$this->active : $this->getDefaultState();
    }
}
