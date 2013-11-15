<?php

namespace Patchwork\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Patchwork\App;
use Patchwork\Helper\RedBean as R;
use Patchwork\Helper\Exception;

abstract class AbstractModel extends \RedBean_SimpleModel
{
    abstract public function getAsserts();



    public function getType()
    {
        return $this->bean->getMeta('type');
    }



    public function hasField($field)
    {
        return array_key_exists($field, $this->getAsserts());
    }



    public function setImage($dir, $file)
    {
        $this->image = $file;
    }



    public function update()
    {
        if ($this->hasField('position')) {
            if ((! $this->position) || ($this->position && count(R::find($this->getType(), 'position = ? AND id != ?', array($this->position, $this->id))))) {
                $position = R::getCell('SELECT position FROM '.$this->getType().' ORDER BY position DESC LIMIT 1');
                $this->position = $position + 1;
            }
        }

        $fields = $this->bean->export();

        foreach ($fields as &$field) {
            $field = strip_tags($field);
        }

        $asserts = $this->getAsserts();

        foreach ($asserts as $key => $assert) {
            if ($assert === null) {
                unset($asserts[$key]);
            }
        }

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



    public function delete()
    {
        if ($this->hasField('position')) {
            R::exec('UPDATE '.$this->getType().' SET position = position - 1 WHERE position > ?', array($this->position));
        }

        if ($this->hasField('image') && $this->image) {
            $dir = BASE_PATH.'/public/assets/img/'.$this->getType().'/';
            unlink($dir.$this->image);
        }
    }
}
