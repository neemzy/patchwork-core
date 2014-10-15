<?php

namespace Patchwork\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

trait FileModel
{
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
     * Copies this model's files for another model
     *
     * @param Patchwork\Model\AbstractModel $clone Target model
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
     * Moves a file in its right place with a generated name
     *
     * @param Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile File to move
     *
     * @return void
     */
    public function moveFile(UploadedFile $uploadedFile)
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
     * File upload callback
     * Attaches the uploaded file to the model for validation
     * and resizes it according to validation constraints if it is an image
     *
     * @param string                                             $field Field name
     * @param Symfony\Component\HttpFoundation\File\UploadedFile $file  File to process
     *
     * @return void
     */
    public function fileUpload($field, UploadedFile $file)
    {
        // Keep current file path to be able to delete it
        $tempField = '_'.$field;
        $this->$tempField = $this->$field;
        $this->$field = $file;
    }



    /**
     * RedBean update method
     * Uploads validated files
     *
     * @return void
     */
    public function fileUpdate()
    {
        foreach ($this->getAsserts() as $field => $asserts) {
            if ($this->$field instanceof UploadedFile) {
                $tempField = '_'.$field;
                is_file($this->$tempField) && unlink($this->$tempField);
                $this->bean->removeProperty($tempField);

                $this->$field = $this->moveFile($this->$field);
            }
        }
    }



    /**
     * RedBean deletion method
     * Deletes files
     *
     * @return void
     */
    public function fileDelete()
    {
        foreach ($this->getAsserts() as $field => $asserts) {
            is_file($this->$field) && unlink($this->$field);
        }
    }
}
