<?php

namespace Patchwork\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use PHPImageWorkshop\ImageWorkshop;
use Patchwork\App;
use Patchwork\Exception;

trait ImageModel
{
    private $imageFile;

    abstract public function getWidth();
    
    abstract public function getHeight();



    public function getImageDir($absolute = true)
    {
        return ($absolute ? BASE_PATH.'/public' : '').'/upload/'.static::unqualify().'/';
    }



    public function getImagePath($absolute = true)
    {
        return $this->getImageDir($absolute).$this->image;
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
        $file = $this->id.'.'.$extension;

        $this->deleteImage(false);
        $image->move($dir, $file);

        $this->image = $file;
        $iw = ImageWorkshop::initFromPath($this->getImagePath());

        $width = null;
        $height = null;
        $currentRatio = $iw->getWidth() / $iw->getHeight();
        $finalWidth = $this->getWidth();
        $finalHeight = $this->getHeight();
        $finalRatio = $finalWidth / $finalHeight;

        if ($currentRatio > $finalRatio) {
            $height = $finalHeight;
        } else {
            $width = $finalWidth;
        }

        $iw->resizeInPixel($width, $height, true, 0, 0, 'MM');
        $iw->cropInPixel($finalWidth, $finalHeight, 0, 0, 'MM');

        $iw->save($dir, $file, false, null, 90);
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



    public function after_update()
    {
        $app = App::getInstance();

        try {
            if ($app['request']->files->has('image') && ($image = $app['request']->files->get('image'))) {
                if ($error = $image->getError()) {
                    $message = 'Une erreur est survenue lors de l\'envoi du fichier';

                    switch ($error) {
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            $message = 'Le fichier sélectionné est trop lourd';
                            break;
                    }
                } else if (! in_array($extension = strtolower($image->guessExtension()), ['jpeg', 'png', 'gif'])) {
                    $message = 'Seuls les formats JPEG, PNG et GIF sont autorisés';
                }

                if (isset($message)) {
                    throw new Exception($message);
                }

                $app['request']->files->remove('image');
                $this->setImage($image, $extension);
            }
        } catch (\RuntimeException $e) {
        }
    }


    public function delete()
    {
        $this->deleteImage();
    }
}
