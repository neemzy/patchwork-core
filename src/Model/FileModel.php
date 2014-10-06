<?php

namespace Patchwork\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Patchwork\App;
use Patchwork\Exception;
use Patchwork\Model\AbstractModel;

trait FileModel
{
    /**
     * Assert collection getter
     *
     * @param bool $files Whether to only keep files or scalar fields
     *
     * @return array Assert collection
     */
    protected function getAsserts($files = false)
    {
        $asserts = parent::getAsserts();

        foreach ($asserts as $key => $assert) {
            $isFile = $assert instanceof Assert\File;

            if (!$isFile && is_array($assert)) {
                foreach ($assert as $constraint) {
                    if ($constraint instanceof Assert\File) {
                        $isFile = true;
                        break;
                    }
                }
            }

            if ($isFile != $files) {
                unset($asserts[$key]);
            }
        }

        return $asserts;
    }



    /**
     * Gets the upload directory path for the current class
     *
     * @return string Upload directory path
     */
    public static function getUploadDir($absolute = true)
    {
        return ($absolute ? BASE_PATH.'/public' : '').'/upload/'.static::unqualify().'/';
    }



    /**
     * Gets the path to this bean's uploaded file
     *
     * @return string Uploaded file path
     */
    public function getFilePath($key, $absolute = true)
    {
        return static::getUploadDir($absolute).$this->$key;
    }



    /**
     * Deletes this bean's uploaded file
     *
     * @param string $key     File to delete
     * @param bool   $persist Whether to persist the deletion into database
     *
     * @return void
     */
    public function deleteFile($key, $persist = false)
    {
        if ($this->$key) {
            $path = $this->getFilePath($key);

            if ($persist) {
                $this->$key = null;
                $this->save();
            }

            file_exists($path) && unlink($path);
        }
    }



    /**
     * Saves an uploaded file for this bean
     *
     * @param string                                             $key          Key under which to save the file
     * @param Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile File to save
     *
     * @return void
     */
    public function upload($key, UploadedFile $uploadedFile)
    {
        $extension = $uploadedFile->guessExtension();

        $dir = $this->getUploadDir();
        $file = '';

        while (file_exists($dir.$file)) {
            $file = uniqid().'.'.$extension;
        }

        $this->deleteFile($key);
        $uploadedFile->move($dir, $file);

        $this->$key = $dir.$file;
        $this->bean->$key = $file;
    }



    /**
     * Copies this bean's file for another bean
     *
     * @param Patchwork\Model\AbstractModel $clone Target bean
     *
     * @return void
     */
    public function cloneFilesFor(AbstractModel $clone)
    {
        foreach ($this->getAsserts(true) as $key => $assert) {
            if ($this->$key) {
                while (file_exists($clone->getFilePath($key))) {
                    $clone->$key = uniqid().'.'.@array_pop(explode('.', $this->$key));
                }

                copy($this->getFilePath($key), $clone->getFilePath($key));
            }
        }
    }



    /**
     * RedBean loading method
     * Truncates file paths
     *
     * @return void
     */
    protected function fileOpen()
    {
        $app = App::getInstance();

        foreach ($this->getAsserts(true) as $key => $assert) {
            $this->$key = $this->bean->$key;
        }
    }



    /**
     * Valorizes this bean with request data
     * Uploads files
     *
     * @return void
     */
    protected function fileHydrate()
    {
        $app = App::getInstance();
        $asserts = $this->getAsserts(true);
        $files = [];

        foreach ($asserts as $key => $assert) {
            if ($app['request']->files->has($key) && ($files[$key] = $app['request']->files->get($key))) {
                $this->$key = $files[$key]->getPathName();
            }
        }

        $this->hydrate();
        $errors = $app['validator']->validate($this);

        if (count($errors)) {
            throw new Exception('Save failed for the following reasons :', 0, null, $errors);
        }

        foreach ($files as $key => $file) {
            $this->upload($key, $file);
        }
    }



    /**
     * RedBean update method
     * Truncates file paths
     *
     * @return void
     */
    protected function fileUpdate()
    {
        foreach ($this->getAsserts(true) as $key => $assert) {
            if ($this->$key && (basename($this->$key) == $this->$key)) {
                // No new file was uploaded, retrieve the full path for validation
                $this->$key = $this->getFilePath($key);
            }
        }
    }



    /**
     * RedBean deletion method
     * Loop-deletes this bean's file
     *
     * @return void
     */
    protected function fileDelete()
    {
        foreach ($this->getAsserts(true) as $key => $assert) {
            $this->deleteFile($key);
        }
    }
}
