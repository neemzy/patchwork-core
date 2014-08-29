<?php

namespace Patchwork\Model;

use Symfony\Component\Validator\Constraints as Assert;
use \RedBean_Facade as R;
use Patchwork\App;
use Patchwork\Exception;
use Patchwork\Tools;

abstract class AbstractModel extends \RedBean_SimpleModel
{
    /**
     * Qualifies a model name
     *
     * @param string $class  The lowercase, short model name
     * @param string $method Method to append to the qualified model name
     *
     * @return string The titlecased, namespaced model name
     */
    public static function qualify($class, $method = null)
    {
        return $app['config']['redbean_prefix'].mb_convert_case($class, MB_CASE_TITLE).(!$method ?: '::'.$method);
    }



    /**
     * Unqualifies a model name
     *
     * @param string $class The titlecase, namespaced model name (the current class's if not provided)
     *
     * @return string The lowercased, shortened model name
     */
    public static function unqualify($class = null)
    {
        $class = explode('\\', $class ?: get_called_class());

        return str_replace('model', '', strtolower(array_pop($class)));
    }



    /**
     * Gets the current class's used traits list
     *
     * @param bool $unqualified Whether to unqualify the traits' names or not
     *
     * @return array Traits list
     */
    protected static function getTraits($unqualified = true)
    {
        $traits = Tools::getRecursiveTraits(get_called_class());

        if ($unqualified) {
            array_walk(
                $traits,
                function (&$trait) {
                    $trait = static::unqualify($trait);
                }
            );
        }

        return $traits;
    }



    /**
     * Determines if the current class uses a given trait
     *
     * @param string $trait Trait full name
     *
     * @return bool Whether this trait is in use in the current class
     */
    protected static function uses($trait)
    {
        return in_array($trait, static::getTraits());
    }



    /**
     * Magic method
     * Catches calls to update and deletion methods to dispatch them first
     *
     * @param string $method    Called method
     * @param array  $arguments Paramaters
     *
     * @return void
     */
    public function __call($method, $arguments)
    {
        if (in_array($method, array('update', 'delete'))) {
            $this->dispatch($method);
            $this->$method();
        }
    }



    /**
     * Calls a given method on all the current's class used traits, based on a prefix
     *
     * @param string $method Method name
     *
     * @return void
     */
    protected function dispatch($method)
    {
        $base = ucfirst($method);

        foreach ($traits = static::getTraits() as $trait) {
            $method = $trait.$base;

            if (method_exists($this, $method)) {
                $this->$method();
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
     * Gets all instances of the model
     *
     * @return array Instance collection
     */
    public static function getAll()
    {
        return R::findAll(static::unqualify(), 'ORDER BY '.static::orderBy());
    }



    /**
     * Hydrates this bean with request data
     *
     * @return void
     */
    public function hydrate()
    {
        $app = App::getInstance();
        
        foreach ($this->getAsserts() as $key => $assert) {
            $this->$key = $app['request']->get($key);
        }
    }



    /**
     * Saves this bean to database
     *
     * @return int|string bean id
     */
    public function save()
    {
        return R::store($this);
    }

    /**
     * Deletes this bean from database
     *
     * @return void
     */
    public function trash()
    {
        R::trash($this);
    }



    /**
     * Trait-overrideable assert list getter
     *
     * @return array Asserts
     */
    protected function getAsserts()
    {
        return $this->asserts();
    }



    /**
     * Validates this bean's values against the model's asserts
     *
     * @throws Patchwork\Exception
     * @return void
     */
    protected function validate()
    {
        $fields = $this->bean->export();

        foreach ($fields as &$field) {
            is_string($field) && $field = strip_tags($field);
        }

        $errors = App::getInstance()['validator']->validateValue(
            $fields,
            new Assert\Collection(
                [
                    'fields' => $this->getAsserts(),
                    'allowExtraFields' => true
                ]
            )
        );

        if (count($errors)) {
            throw new Exception('Save failed for the following reasons :', 0, null, $errors);
        }
    }



    /**
     * RedBean update method
     *
     * @return void
     */
    private function update()
    {
        $this->validate();
    }



    /**
     * RedBean deletion method
     *
     * @return void
     */
    private function delete()
    {
    }
}
