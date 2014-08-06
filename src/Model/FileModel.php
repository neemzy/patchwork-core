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
     * @var array File asserts keys list
     */
    protected $fileFields = [];



    /**
     * Trait-overrideable assert list getter
     *
     * @return array Asserts
     */
    protected function getAsserts($classic = true, $files = false)
    {
        $asserts = $this->asserts();

        if ($classic && $files) {
            return $asserts;
        }

        $classicAsserts = [];
        $fileAsserts = [];

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

            if ($isFile) {
                $fileAsserts[$key] = $assert;
            } else {
                $classicAsserts[$key] = $assert;
            }
        }

        if ($classic) {
            return $classicAsserts;
        }

        if ($files) {
            return $fileAsserts;
        }
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
     * @param $key     string File to delete
     * @param $persist bool   Whether to persist the deletion into database
     *
     * @return void
     */
    public function deleteFile($key, $persist = false)
    {
        if ($this->$key) {
            if ($persist) {
                $asserts = $this->getAsserts(false, true);

                foreach ($asserts[$key] as $constraint) {
                    if ($constraint instanceof Assert\NotBlank) {
                        $errors = [];
                        $errors[] = new ConstraintViolation('This value should not be blank.', null, [], null, '['.$key.']', null);

                        throw new Exception('Files are errored :', 0, null, $errors);
                    }
                }
            }

            unlink($this->getFilePath($key));
            $this->$key = null;

            $persist && $this->save();
        }
    }



    /**
     * Saves an uploaded file for this bean
     *
     * @param $key          string                                             Key under which to save the file
     * @param $uploadedFile Symfony\Component\HttpFoundation\File\UploadedFile File to save
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
        $this->$key = $file;
    }



    /**
     * Copies this bean's file for another bean
     *
     * @param Patchwork\Model\AbstractModel string Target bean
     *
     * @return void
     */
    public function cloneFilesFor(Patchwork\Model\AbstractModel $clone)
    {
        foreach ($this->getAsserts(false, true) as $key => $asserts) {
            if ($this->$key) {
                while (file_exists($clone->getFilePath($key))) {
                    $clone->$key = uniqid().'.'.array_pop(explode('.', $this->$key));
                }

                $clone->save();
                copy($this->getFilePath($key), $clone->getFilePath($key));
            }
        }
    }



    /**
     * RedBean update method
     * Uploads and validates files
     *
     * @return void
     */
    protected function fileUpdate()
    {
        $app = App::getInstance();
        $exception = null;

        try {
            $this->validate();
        } catch (Exception $e) {
            $exception = $e;
        }

        try {
            $errors = [];

            foreach ($this->getAsserts(false, true) as $key => $asserts) {
                $messages = [];
                $required = false;

                foreach ($asserts as $constraint) {
                    if ($constraint instanceof Assert\NotBlank) {
                        $required = true;
                        break;
                    }
                }

                if ($app['request']->files->has($key) && ($file = $app['request']->files->get($key))) {
                    if ($error = $file->getError()) {
                        $messages[] = $error;
                    } else {
                        foreach ($app['validator']->validateValue($file, $asserts) as $error) {
                            $messages[] = $error->getMessage();
                        }
                    }
                    
                    if (!$exception && !count($messages)) {
                        $this->upload($key, $file);
                    }
                } else {
                    if ($required && !$this->$key) {
                        $messages[] = 'This value should not be blank.';
                    }
                }

                foreach ($messages as $message) {
                    $errors[] = new ConstraintViolation($message, null, [], null, '['.$key.']', null);
                }
            }

            if ($exception) {
                $details = $exception->getDetails();
                $exception->setDetails(array_merge($details, $errors));

                throw $exception;
            }

            if (count($errors)) {
                throw new Exception('Files are errored :', 0, null, $errors);
            }
        } catch (\RuntimeException $e) {
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
        try {
            foreach ($this->getAsserts(false, true) as $key => $asserts) {
                if ($this->$key) {
                    $this->deleteFile($key);
                }
            }
        } catch (Exception $e) {
        }
    }
}
