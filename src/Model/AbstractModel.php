<?php

namespace Patchwork\Model;

use Symfony\Component\Validator\Constraints as Assert;
use \RedBean_Facade as R;
use Patchwork\App;
use Patchwork\Exception;

abstract class AbstractModel extends \RedBean_SimpleModel
{
    protected static function asserts() {
        return [];
    }



    public static function unqualify()
    {
        $class = explode('\\', get_called_class());

        return strtolower(array_pop($class));
    }



    public static function orderBy()
    {
        return 'id ASC';
    }



    public static function getAll()
    {
        return R::findAll(static::unqualify(), 'ORDER BY '.static::orderBy());
    }



    public function hydrate()
    {
        $app = App::getInstance();
        $pristine = !$this->id;
        
        foreach ($this->asserts($pristine) as $key => $assert) {
            $this->$key = $app['request']->get($key);
        }
    }



    public function save()
    {
        return R::store($this);
    }



    public function update($bubble = true)
    {
        $fields = $this->bean->export();

        foreach ($fields as &$field) {
            $field = strip_tags($field);
        }

        $errors = App::getInstance()['validator']->validateValue(
            $fields,
            new Assert\Collection(
                [
                    'fields' => $this->asserts(),
                    'allowExtraFields' => true
                ]
            )
        );

        if (count($errors)) {
            throw new Exception('L\'enregistrement a échoué pour les raisons suivantes :', 0, null, $errors);
        }
    }



    public function trash()
    {
        return R::trash($this);
    }
}
