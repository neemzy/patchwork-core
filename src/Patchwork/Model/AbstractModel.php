<?php

namespace Patchwork\Model;

use Silex\Application;
use Symfony\Component\Validator\Constraints as Assert;
use Patchwork\Helper\RedBean as R;
use Patchwork\Helper\Exception;

abstract class AbstractModel extends \RedBean_SimpleModel
{
    abstract public function getAsserts();



    public function update()
    {
        $fields = $this->bean->export();

        foreach ($fields as &$field) {
            $field = strip_tags($field);
        }

        $errors = $this->app['validator']->validateValue(
            $fields,
            new Assert\Collection(
                array(
                    'fields' => $this->getAsserts(),
                    'allowExtraFields' => true
                )
            )
        );

        if (count($errors)) {
            throw new Exception('L\'enregistrement a échoué pour les raisons suivantes :', 0, null, $errors);
        }
    }
}
