<?php

namespace Patchwork\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Patchwork\App;
use Patchwork\ValidatableInterface;
use Patchwork\Tools;
use Neemzy\Silex\Provider\RedBean\Model;

abstract class AbstractModel extends Model implements ValidatableInterface
{
    /**
     * Gets the model's database table name
     *
     * @return string
     */
    public function getTableName()
    {
        $class = explode('\\', get_class($this));

        return strtolower(array_pop($class));
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
        $traits = Tools::getRecursiveTraits(get_class($this));

        foreach ($traits as $trait) {
            $trait = str_replace('model', '', strtolower(@array_pop(explode('\\', $trait))));
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
     * Model validation metadata getter
     *
     * @return array
     */
    public function getAsserts()
    {
        $metadata = App::getInstance()['validator.mapping.class_metadata_factory']->getMetadataFor(get_class($this));

        return array_map(
            function ($member) {
                return $member[0]->constraints;
            },
            $metadata->members
        );
    }



    /**
     * Gets the upload directory path for the current class
     *
     * @return string
     */
    public function getUploadDir($absolute = true)
    {
        return ($absolute ? BASE_PATH.'/public' : '').'/upload/'.$this->getTableName().'/';
    }



    /**
     * Gets a file's web path
     *
     * @return string
     */
    public function getWebPath($field)
    {
        return $this->getUploadDir(false).str_replace($this->getUploadDir(), '', $this->$field);
    }



    /**
     * Uploads a file
     *
     * @param Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile File to save
     *
     * @return void
     */
    public function upload(UploadedFile $uploadedFile)
    {
        $extension = $uploadedFile->guessExtension();

        $dir = $this->getUploadDir();
        $file = '';

        while (file_exists($dir.$file)) {
            $file = uniqid().'.'.$extension;
        }

        $uploadedFile->move($dir, $file);
        return $dir.$file;
    }



    /**
     * Copies this bean's files for another bean
     *
     * @param Patchwork\Model\AbstractModel $clone Target bean
     *
     * @return void
     */
    public function cloneFilesFor(AbstractModel $clone)
    {
        foreach ($this->getAsserts() as $field => $asserts) {
            if (is_file($this->$field)) {
                $dir = $this->getUploadDir();
                $file = basename($this->$field);
                $extension = @array_pop(explode('.', $file));

                while (file_exists($dir.$file)) {
                    $file = uniqid().'.'.$extension;
                }

                $clone->$field = $dir.$file;
                copy($this->$field, $clone->$field);
            }
        }
    }



    /**
     * RedBean update method
     * Uploads validated files
     *
     * @return void
     */
    public function update()
    {
        $this->dispatch('update');

        foreach ($this->getAsserts() as $field => $asserts) {
            if ($this->$field instanceof UploadedFile) {
                $tempField = '_'.$field;
                is_file($this->$tempField) && unlink($this->$tempField);
                $this->bean->removeProperty($tempField);

                $this->$field = $this->upload($this->$field);
            }
        }
    }



    /**
     * RedBean deletion method
     * Deletes files
     *
     * @return void
     */
    public function delete()
    {
        $this->dispatch('delete');

        foreach ($this->getAsserts() as $field => $asserts) {
            is_file($this->$field) && unlink($this->$field);
        }
    }
}
