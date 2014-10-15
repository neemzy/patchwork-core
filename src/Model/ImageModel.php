<?php

namespace Neemzy\Patchwork\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Image;
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
    public function imageUpload($field, UploadedFile $file)
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
                        $this->app['tools']->resize($pathWithExtension, $assert->maxWidth, $assert->maxHeight);
                    } catch (ImageWorkshopException $e) {
                    }

                    rename($pathWithExtension, $path);
                }
            }
        }
    }
}
