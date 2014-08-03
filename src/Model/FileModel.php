<?php

namespace Patchwork\Model;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolation;
use PHPImageWorkshop\ImageWorkshop;
use Patchwork\App;
use Patchwork\Exception;

trait FileModel
{
    protected $fileFields = array();



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





    public static function getUploadDir($absolute = true)
    {
        return ($absolute ? BASE_PATH.'/public' : '').'/upload/'.static::unqualify().'/';
    }

    public function getFilePath($field, $absolute = true)
    {
        return static::getUploadDir($absolute).$this->$field;
    }



    public function deleteFile($field, $persist = true)
    {
        if ($this->$field) {
            unlink($this->getFilePath($field));
            $this->$field = null;

            $persist && $this->save();
        }
    }



    public function resize($key, $finalWidth, $finalHeight, $quality = 90)
    {
        $width = $finalWidth;
        $height = $finalHeight;
        $crop = $width && $height;

        $dir = $this->getUploadDir();
        $iw = ImageWorkshop::initFromPath($dir.$this->$key);

        if ($crop) {
            $originalRatio = $iw->getWidth() / $iw->getHeight();
            $finalRatio = $finalWidth / $finalHeight;

            if ($originalRatio > $finalRatio) {
                $width = null;
            } else {
                $height = null;
            }
        }

        $iw->resizeInPixel($width, $height, true, 0, 0, 'MM');
        $crop && $iw->cropInPixel($finalWidth, $finalHeight, 0, 0, 'MM');
        $iw->save($dir, $this->$key, false, null, $quality);
    }

    public function upload($field, UploadedFile $uploadedFile)
    {
        $extension = $uploadedFile->guessExtension();

        $dir = $this->getUploadDir();
        $file = '';

        while (file_exists($dir.$file)) {
            $file = uniqid().'.'.$extension;
        }

        $this->deleteFile($field, false);
        $uploadedFile->move($dir, $file);
        $this->$field = $file;
    }

    public function cloneFilesFor($clone)
    {
        foreach ($this->getAsserts(false, true) as $field => $asserts) {
            if ($this->$field) {
                while (file_exists($clone->getFilePath($field))) {
                    $clone->$field = uniqid().'.'.array_pop(explode('.', $this->$field));
                }

                $clone->save();
                copy($this->getFilePath($field), $clone->getFilePath($field));
            }
        }
    }



    protected function fileUpdate()
    {
        $app = App::getInstance();

        try {
            $errors = new ConstraintViolationList();

            foreach ($this->getAsserts(false, true) as $key => $asserts) {
                $messages = [];
                $required = false;
                $width = null;
                $height = null;

                foreach ($asserts as $constraint) {
                    if ($constraint instanceof Assert\NotBlank) {
                        $required = true;
                    } else if ($constraint instanceof Assert\Image) {
                        $width = $constraint->maxWidth;
                        $height = $constraint->maxHeight;
                        $constraint->maxWidth = null;
                        $constraint->maxHeight = null;
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
                    
                    if (!count($messages)) {
                        $this->upload($key, $file);

                        if ($width || $height) {
                            $this->resize($key, $width, $height);
                        }
                    }
                } else if ($required && !$this->$key) {
                    $messages[] = 'This value should not be blank.';
                }

                foreach ($messages as $message) {
                    $errors->add(new ConstraintViolation($message, null, [], null, '['.$key.']', null));
                }
            }

            if (count($errors)) {
                throw new Exception('Files are errored :', 0, null, $errors);
            }
        } catch (\RuntimeException $e) {
        }
    }

    protected function fileDelete()
    {
        try {
            foreach ($this->getAsserts(false, true) as $field => $asserts) {
                if ($this->$field) {
                    $this->deleteFile($field);
                }
            }
        } catch (Exception $e) {
        }
    }
}
