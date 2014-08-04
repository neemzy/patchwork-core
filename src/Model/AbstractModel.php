<?php

namespace Patchwork\Model;

use Symfony\Component\Validator\Constraints as Assert;
use \RedBean_Facade as R;
use Patchwork\App;
use Patchwork\Exception;

abstract class AbstractModel extends \RedBean_SimpleModel
{
    public static function unqualify($class = null)
    {
        $class = explode('\\', $class ?: get_called_class());

        return str_replace('model', '', strtolower(array_pop($class)));
    }

    private static function getRecursiveTraits($class)
    {
        $reflection = new \ReflectionClass($class);
        $traits = array_keys($reflection->getTraits());

        foreach ($traits as $trait) {
            $traits = array_merge($traits, static::getRecursiveTraits($trait, $level));
        }

        return $traits;
    }

    protected static function getTraits($unqualified = true)
    {
        $traits = static::getRecursiveTraits(get_called_class());

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

    protected static function uses($trait)
    {
        return in_array($trait, static::getTraits());
    }

    public function __call($method, $arguments)
    {
        if (in_array($method, array('update', 'delete'))) {
            $this->dispatch($method);
            $this->$method();
        }
    }

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



    public static function orderBy()
    {
        return 'id ASC';
    }

    public static function getAll()
    {
        return R::findAll(static::unqualify(), 'ORDER BY '.static::orderBy());
    }



    public function isPristine()
    {
        return !$this->id;
    }

    public function hydrate()
    {
        $app = App::getInstance();
        
        foreach ($this->getAsserts() as $key => $assert) {
            $this->$key = $app['request']->get($key);
        }
    }

    public function save()
    {
        return R::store($this);
    }

    public function trash()
    {
        return R::trash($this);
    }



    protected function getAsserts()
    {
        return $this->asserts();
    }

    private function update()
    {
        $fields = $this->bean->export();

        foreach ($fields as &$field) {
            $field = strip_tags($field);
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

    private function delete()
    {
    }
}
