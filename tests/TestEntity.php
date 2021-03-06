<?php

namespace Neemzy\Patchwork\Tests;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Neemzy\Patchwork\Model\Entity;
use Neemzy\Patchwork\Model\FileModel;
use Neemzy\Patchwork\Model\SlugModel;
use Neemzy\Patchwork\Model\SortableModel;
use Neemzy\Patchwork\Model\TogglableModel;

class TestEntity extends Entity
{
    use FileModel, SlugModel, SortableModel, TogglableModel;



    /**
     * Catch-all property getter to avoid reaching the inner bean
     *
     * @param string $name Property name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->$name;
    }



    /**
     * Catch-all property setter to avoid reaching the inner bean
     *
     * @param string $name  Property name
     * @param mixed  $value Assigned value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $this->$name = $value;
    }



    /**
     * Blank interface method implementation
     *
     * @return void
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
    }
}
