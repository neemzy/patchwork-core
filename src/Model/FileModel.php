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
                $path = $this->getUploadPath();
                $file = $this->$field;
                $extension = @array_pop(explode('.', $file));

                while (file_exists($path.$file)) {
                    $file = $this->generateFilename($extension);
                }

                $clone->$field = $path.$file;
                copy($this->$field, $clone->$field);
            }
        }
    }



    /**
     * Gets upload path
     *
     * @param bool $absolute Whether the path should be absolute
     *
     * @return string
     */
    protected function getUploadPath($absolute = true)
    {
        return ($absolute ? $this->app['base_path'].'/public' : '').'/upload/'.$this->getTableName().'/';
    }



    /**
     * Moves a file in its right place with a generated name
     *
     * @param Symfony\Component\HttpFoundation\File\UploadedFile $file File to move
     *
     * @return string New file name
     */
    protected function moveFile(UploadedFile $file)
    {
        $path = $this->getUploadPath();
        $name = $this->generateFilename($path, $file->guessExtension());

        while (file_exists($path.$name)) {
            $name = $this->generateFilename($extension);
        }

        $file->move($path, $name);
        return $name;
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
    protected function fileUpload($field, UploadedFile $file)
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
                $tempFile = $this->getFilePath($tempField, true);
                is_file($tempFile) && unlink($tempFile);
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
            $file = $this->getFilePath($field, true);

            is_file($file) && unlink($file);
        }
    }



    /**
     * Generates unique file name
     *
     * @param string $path      Directory in which the file will be saved
     * @param string $extension File extension
     *
     * @return string
     */
    private function generateFilename($path, $extension)
    {
        $file = '';

        while (!$file || is_file($path.$file)) {
            $file = uniqid().'.'.$extension;
        }

        return $file;
    }
}
