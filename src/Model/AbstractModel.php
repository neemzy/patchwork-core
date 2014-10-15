<?php

namespace Neemzy\Patchwork\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Neemzy\Patchwork\ValidatableInterface;
use Neemzy\Silex\Provider\RedBean\Model;

abstract class AbstractModel extends Model implements ValidatableInterface
{
    /**
     * Gets the model's database table name
     *
     * @return string
     */
    public static function getTableName()
    {
        $class = explode('\\', get_called_class());

        return strtolower(array_pop($class));
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
        $traits = $this->app['tools']->getRecursiveTraits(get_class($this));

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
     * Overrideable method to get the default sorting index and way
     *
     * @return string SQL snippet
     */
    public static function orderBy()
    {
        return 'id ASC';
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
}
