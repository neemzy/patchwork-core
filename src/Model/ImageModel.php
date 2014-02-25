<?php

namespace Patchwork\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use PHPImageWorkshop\ImageWorkshop;
use Patchwork\App;
use Patchwork\Helper\RedBean as R;
use Patchwork\Helper\Exception;

trait ImageModel
{
    private $imageFile;



    private static function getResizeValues($currentWidth, $currentHeight, $desiredWidth, $desiredHeight)
    {
        $width = null;
        $height = null;
        $currentRatio = $currentWidth / $currentHeight;
        $desiredRatio = $desiredWidth / $desiredHeight;

        if ($currentRatio > $desiredRatio) {
            $height = $desiredHeight;
        } else {
            $width = $desiredWidth;
        }

        return array($width, $height);
    }



    public function getImageDir($absolute = true)
    {
        return ($absolute ? BASE_PATH : '').'/public/upload/'.$this->getType().'/';
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

            $persist && R::store($this);
        }
    }



    public function setImage(UploadedFile $image, $extension)
    {
        $dir = $this->getImageDir();
        $file = $this->id.'.'.$extension;

        $this->deleteImage(false);
        $image->move($dir, $file);

        $iw = ImageWorkshop::initFromPath($dir.$file);
        list($width, $height) = self::getResizeValues($iw->getWidth(), $iw->getHeight(), static::WIDTH, static::HEIGHT);

        $iw->resizeInPixel($width, $height, true, 0, 0, 'MM');
        $iw->cropInPixel(static::WIDTH, static::HEIGHT, 0, 0, 'MM');
        $iw->save($dir, $file, false, null, 90);
        
        $this->image = $file;
    }



    public function update()
    {
        $app = App::getInstance();

        if ($app['request']->files->has('image') && ($image = $app['request']->files->get('image'))) {
            if ($error = $image->getError()) {
                $message = 'Une erreur est survenue lors de l\'envoi du fichier';

                switch ($error) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $message = 'Le fichier sélectionné est trop lourd';
                        break;
                }
            } else if (! in_array($extension = strtolower($image->guessExtension()), array('jpeg', 'png', 'gif'))) {
                $message = 'Seuls les formats JPEG, PNG et GIF sont autorisés';
            }

            if (isset($message)) {
                throw new Exception($message);
            }

            $this->setImage($image, $extension);
        }
    }


    public function delete()
    {
        $this->deleteImage();
    }
}
