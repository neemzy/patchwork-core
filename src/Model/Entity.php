<?php

namespace Neemzy\Patchwork\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Neemzy\Silex\Provider\RedBean\Model;

abstract class Entity extends Model implements ValidatableInterface
{
    /**
     * Gets the model's database table name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->bean->getMeta('type');
    }



    /**
     * Model validation metadata getter
     *
     * @return array
     */
    public function getAsserts()
    {
        $metadata = $this->app['validator.mapping.class_metadata_factory']->getMetadataFor(get_class($this));

        return array_map(
            function ($member) {
                return $member[0]->constraints;
            },
            $metadata->members
        );
    }



    /**
     * Calls a given method on all the current's class used traits, based on a prefix
     *
     * @param string $method     Method name
     * @param array  $parameters Method parameters
     *
     * @return void
     */
    public function dispatch($method, $parameters = [])
    {
        $base = ucfirst($method);
        $traits = $this->getRecursiveTraits();

        foreach ($traits as $trait) {
            $trait = explode('\\', $trait);
            $trait = str_replace('model', '', strtolower(array_pop($trait)));

            $method = $trait.$base;

            if (method_exists($this, $method)) {
                call_user_func_array([$this, $method], $parameters);
            }
        }
    }



    /**
     * RedBean update method
     * Dispatches event across model traits
     *
     * @return void
     */
    public function update()
    {
        $this->dispatch('update');
    }



    /**
     * RedBean deletion method
     * Dispatches event across model traits
     *
     * @return void
     */
    public function delete()
    {
        $this->dispatch('delete');
    }



    /**
     * Gets a recursive list of traits used by a class
     *
     * @param string $class Full class name
     *
     * @return array
     */
    private function getRecursiveTraits($class = null)
    {
        if (null == $class) {
            $class = get_class($this);
        }

        $reflection = new \ReflectionClass($class);
        $traits = array_keys($reflection->getTraits());

        foreach ($traits as $trait) {
            $traits = array_merge($traits, $this->getRecursiveTraits($trait));
        }

        if ($parent = $reflection->getParentClass()) {
            $traits = array_merge($traits, $this->getRecursiveTraits($parent->getName()));
        }

        return $traits;
    }
}
