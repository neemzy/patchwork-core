<?php

namespace Patchwork;

use PHPImageWorkshop\ImageWorkshop;

class Tools
{
    /**
     * Dumps a variable
     *
     * @param mixed $var Variable to dump
     * @param bool  $pre Whether to wrap the result in a HTML <pre> tag or not
     *
     * @return string
     */
    public function dump($var, $pre = false)
    {
        ob_start();
        var_dump($var);
        $dump = ob_get_clean();

        $pre && $dump = '<pre class="debug">'.$dump.'</pre>';
        return $dump;
    }



    /**
     * Makes a string URL-suitable
     *
     * @param string $string String to vulgarize
     *
     * @return string
     */
    public function vulgarize($string)
    {
        return trim(
            preg_replace(
                '/(-+)/',
                '-',
                preg_replace(
                    '/([^a-z0-9-]*)/',
                    '',
                    preg_replace(
                        '/((\s|\.|\'|\/)+)/',
                        '-',
                        html_entity_decode(
                            preg_replace(
                                '/&(a|o)elig;/',
                                '$1e',
                                preg_replace(
                                    '/&([a-z])(uml|acute|grave|circ|tilde|ring|cedil|slash);/',
                                    '$1',
                                    strtolower(
                                        htmlentities(
                                            $string,
                                            ENT_COMPAT,
                                            'utf-8'
                                        )
                                    )
                                )
                            ),
                            ENT_COMPAT,
                            'utf-8'
                        )
                    )
                )
            ),
            '-'
        );
    }



    /**
     * Resizes an image
     *
     * @param string $file Full file path
     * @param int    $width   Maximum width
     * @param int    $height  Maximum height
     * @param int    $quality Quality ratio
     */
    public function resize($file, $width = null, $height = null, $quality = 90)
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



    /**
     * Gets a recursive list of traits used by a class
     *
     * @param string $class Full class name
     *
     * @return array
     */
    public function getRecursiveTraits($class)
    {
        $reflection = new \ReflectionClass($class);
        $traits = array_keys($reflection->getTraits());

        foreach ($traits as $trait) {
            $traits = array_merge($traits, $this->getRecursiveTraits($trait));
        }

        if ($parent = $reflection->getParentClass()) {
            $traits = array_merge($traits, $this->getRecursiveTraits($parent->getName()));
        }

        return $traits;
    }
}
