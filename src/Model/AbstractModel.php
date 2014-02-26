<?php

namespace Patchwork\Model;

use Symfony\Component\Validator\Constraints as Assert;
use \RedBean_Facade as R;
use Patchwork\App;
use Patchwork\Helper\Exception;

abstract class AbstractModel extends \RedBean_SimpleModel
{
    abstract protected function asserts();

    public function getAsserts($files = true)
    {
        $asserts = $this->asserts();

        if (! $files) {
            foreach ($asserts as $key => $assert) {
                if (is_array($assert)) {
                    foreach ($assert as $index => $rule) {
                        if ($rule instanceof Assert\File) {
                            unset($asserts[$key][$index]);
                        }
                    }

                    if (! count($assert)) {
                        unset($asserts[$key]);
                    }
                } else if ($assert instanceof Assert\File) {
                    unset($asserts[$key]);
                }
            }
        }

        return $asserts;
    }



    abstract protected function orderBy();

    public function getAll()
    {
        return R::findAll($this->getType(), 'ORDER BY '.$this->orderBy());
    }



    public function getType()
    {
        return $this->bean->getMeta('type');
    }



    public function update()
    {
        $fields = $this->bean->export();

        foreach ($fields as &$field) {
            $field = strip_tags($field);
        }

        $asserts = $this->getAsserts(false);

        $errors = App::getInstance()['validator']->validateValue(
            $fields,
            new Assert\Collection(
                array(
                    'fields' => $asserts,
                    'allowExtraFields' => true
                )
            )
        );

        if (count($errors)) {
            throw new Exception('L\'enregistrement a échoué pour les raisons suivantes :', 0, null, $errors);
        }
    }
}
