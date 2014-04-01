<?php

namespace Patchwork\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use PHPImageWorkshop\ImageWorkshop;
use Patchwork\App;
use Patchwork\Exception;

trait ImageModel
{
    private $imageFile;



    public static function width()
    {
        return null;
    }
    
    public static function height()
    {
        return null;
    }



    public static function getImageDir($absolute = true)
    {
        return ($absolute ? BASE_PATH.'/public' : '').'/upload/'.static::unqualify().'/';
    }

    public function getImagePath($absolute = true)
    {
        return static::getImageDir($absolute).$this->image;
    }



    public function deleteImage($persist = true)
    {
        if ($this->image) {
            unlink($this->getImagePath());
            $this->image = null;

            $persist && $this->save();
        }
    }

    public function setImage(UploadedFile $image, $extension)
    {
        $dir = $this->getImageDir();
        $file = '';

        while (file_exists($dir.$file)) {
            $file = uniqid().'.'.$extension;
        }

        $this->deleteImage(false);
        $image->move($dir, $file);
        $this->image = $file;

        $finalWidth = static::width();
        $finalHeight = static::height();

        if ($finalWidth || $finalHeight) {
            $width = $finalWidth;
            $height = $finalHeight;
            $crop = ($finalWidth && $finalHeight);
            $iw = ImageWorkshop::initFromPath($this->getImagePath());

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

            $iw->save($dir, $file, false, null, 90);
        }

        $this->save();
    }

    public function cloneImageFor($clone)
    {
        if ($this->image) {
            $clone->image = $clone->id.'.'.array_pop(explode('.', $this->image));
            $clone->save();

            copy($this->getImagePath(), $clone->getImagePath());
        }
    }



    protected function imageUpdate()
    {
        $app = App::getInstance();

        try {
            if ($app['request']->files->has('image') && ($image = $app['request']->files->get('image'))) {
                if ($error = $image->getError()) {
                    $message = 'An error occured during the file upload.';

                    switch ($error) {
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            $message = 'The selected file is too big.';
                            break;
                    }
                } else if (! in_array($extension = strtolower($image->guessExtension()), ['jpeg', 'png', 'gif'])) {
                    $message = 'Only JPEG, PNG and GIF types are allowed.';
                }

                if (isset($message)) {
                    throw new Exception($message);
                }
                
                $this->setImage($image, $extension);
            }
        } catch (\RuntimeException $e) {
        }
    }

    protected function imageDelete()
    {
        $this->deleteImage();
    }
}
