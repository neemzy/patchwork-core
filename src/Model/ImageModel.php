<?php

namespace Patchwork\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use PHPImageWorkshop\ImageWorkshop;

trait ImageModel
{
    use FileModel {
        FileModel::getAsserts as _getAsserts;
        FileModel::upload as _upload;
    }



    protected function getAsserts($classic = true, $files = false, $keepSizes = false)
    {
        $asserts = $this->_getAsserts($classic, $files);

        if ($files && !$keepSizes) {
            foreach ($asserts as $key => $assert) {
                foreach ($assert as &$constraint) {
                    if ($constraint instanceof Assert\Image) {
                        $constraint->maxWidth = null;
                        $constraint->maxHeight = null;
                    }
                }
            }
        }

        return $asserts;
    }



    public function resize($key, $quality = 90)
    {
        $width = null;
        $height = null;

        foreach ($this->getAsserts(false, true, true)[$key] as $constraint) {
            if ($constraint instanceof Assert\Image) {
                $width = $constraint->maxWidth;
                $height = $constraint->maxHeight;
            }
        }

        if ($width || $height) {
            $finalWidth = $width;
            $finalHeight = $height;
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
    }



    public function upload($key, UploadedFile $uploadedFile)
    {
        $this->_upload($key, $uploadedFile);

        $this->resize($key);
    }
}
