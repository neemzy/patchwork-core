<?php

namespace Neemzy\Patchwork\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

trait FileModel
{
    /**
     * Gets a file's path
     *
     * @param bool $absolute Whether the path should be absolute
     *
     * @return string
     */
    public function getFilePath($field, $absolute = false)
    {
        if (empty($this->$field) || is_file($this->$field)) {
            return $this->$field;
        }

        return $this->getUploadPath($absolute).$this->$field;
    }



    /**
     * Copies this model's files for another model
     *
     * @param Neemzy\Patchwork\Model\Entity $clone Target model
     *
     * @return void
     */
    public function cloneFilesFor(Entity $clone)
    {
        foreach ($this->getAsserts() as $field => $asserts) {
            if (is_file($this->$field)) {
                $dir = $this->getUploadPath();
                $file = $this->$field;
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
     * @return string New file name
     */
    public function moveFile(UploadedFile $uploadedFile)
    {
        $extension = $uploadedFile->guessExtension();

        $dir = $this->getUploadPath();
        $file = '';

        while (file_exists($dir.$file)) {
            $file = uniqid().'.'.$extension;
        }

        $uploadedFile->move($dir, $file);
        return $file;
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
     * Uploads files
     *
     * @return void
     */
    protected function fileUpdate()
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
    protected function fileDelete()
    {
        foreach ($this->getAsserts() as $field => $asserts) {
            $file = $this->getFilePath($field);

            is_file($file) && unlink($file);
        }
    }



    /**
     * Gets upload path
     *
     * @param bool $absolute Whether the path should be absolute
     *
     * @return string
     */
    private function getUploadPath($absolute)
    {
        return ($absolute ? $app['base_path'].'/public' : '').'/upload/'.$this->getTableName().'/';
    }
}
