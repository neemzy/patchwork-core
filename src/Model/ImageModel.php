<?php

namespace Neemzy\Patchwork\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Image;
use PHPImageWorkshop\ImageWorkshop;
use PHPImageWorkshop\Exception\ImageWorkshopException;

trait ImageModel
{
    use FileModel;

    /**
     * File upload callback
     * Resizes the image according to validation constraints
     *
     * @param string                                             $field Field name
     * @param Symfony\Component\HttpFoundation\File\UploadedFile $file  File to process
     *
     * @return void
     */
    protected function imageUpload($field, UploadedFile $file)
    {
        if (UPLOAD_ERR_OK == $file->getError()) {
            $asserts = $this->getAsserts()[$field];

            foreach ($asserts as $assert) {
                // Detect image size constraints and resize accordingly
                if ($assert instanceof Image) {
                    $path = $file->getPathname();
                    $pathWithExtension = $path.'.'.$file->guessExtension();
                    rename($path, $pathWithExtension);

                    // ImageWorkshop relies on the file's extension for encoding
                    try {
                        $this->resize($pathWithExtension, $assert->maxWidth, $assert->maxHeight);
                    } catch (ImageWorkshopException $e) {
                    }

                    rename($pathWithExtension, $path);
                }
            }
        }
    }



    /**
     * Resizes an image
     *
     * @param string $file Full file path
     * @param int    $width   Maximum width
     * @param int    $height  Maximum height
     * @param int    $quality Quality ratio
     */
    private function resize($file, $width = null, $height = null, $quality = 90)
    {
        if ($width || $height) {
            $finalWidth = $width;
            $finalHeight = $height;
            $crop = $width && $height;

            $iw = ImageWorkshop::initFromPath($file);

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

            $iw->save(dirname($file), basename($file), false, null, $quality);
        }
    }
}
