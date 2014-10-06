<?php

namespace Patchwork\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use PHPImageWorkshop\ImageWorkshop;
use Patchwork\App;
use Patchwork\Exception;

trait ImageModel
{
    use FileModel {
        FileModel::upload as _upload;
    }



    /**
     * Resizes this bean's image
     *
     * @param string $key     Image to resize
     * @param int    $quality Quality percentage
     *
     * @return void
     */
    public function resize($key, $quality = 90)
    {
        $width = null;
        $height = null;

        foreach ($this->getAsserts(true)[$key] as $constraint) {
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
            $file = $this->bean->$key;
            $iw = ImageWorkshop::initFromPath($dir.$file);

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
            $iw->save($dir, $file, false, null, $quality);
        }
    }



    /**
     * Saves an uploaded image for this bean
     *
     * @param string                                             $key          Key under which to save the file
     * @param Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile File to save
     *
     * @return void
     */
    public function upload($key, UploadedFile $uploadedFile)
    {
        $this->_upload($key, $uploadedFile);

        $this->resize($key);
    }



    /**
     * Filters image size errors
     *
     * @return array Filtered error collection
     */
    protected static function filterErrors($errors)
    {
        $filteredErrors = [];

        foreach ($errors as $error) {
            if (!preg_match('/(width|height) is too big/', $error->getMessage())) {
                $filteredErrors[] = $error;
            }
        }

        return $filteredErrors;
    }



    /**
     * Valorizes this bean with request data
     * Uploads files while ignoring image size errors
     *
     * @return void
     */
    protected function fileHydrate()
    {
        $app = App::getInstance();
        $asserts = $this->getAsserts(true);
        $files = [];

        foreach ($asserts as $key => $assert) {
            if ($app['request']->files->has($key) && ($files[$key] = $app['request']->files->get($key))) {
                $this->$key = $files[$key]->getPathName();
            }
        }

        $this->hydrate();
        $errors = static::filterErrors($app['validator']->validate($this));

        if (count($errors)) {
            throw new Exception('Save failed for the following reasons :', 0, null, $errors);
        }

        foreach ($files as $key => $file) {
            $this->upload($key, $file);
        }
    }



    /**
     * RedBean update method
     * Hides image size errors
     *
     * @return void
     */
    protected function update()
    {
        $errors = static::filterErrors(App::getInstance()['validator']->validate($this));

        if (count($errors)) {
            throw new Exception('Save failed for the following reasons :', 0, null, $errors);
        }
    }
}
