<?php

namespace Patchwork;

class Tools
{
    public static function twitter($url, $text = '')
    {
        return 'http://twitter.com/share?url='.rawurlencode($url).'&amp;text='.rawurlencode($text).'" onclick="window.open(this.href, \'\', \'directories=no,location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no,width=640,height=435\');return false';
    }



    public static function facebook($url)
    {
        return 'http://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url).'" onclick="window.open(this.href, \'\', \'directories=no,location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no,width=640,height=350\');return false';
    }



    public static function pinterest($url, $media)
    {
        return 'http://pinterest.com/pin/create/button/?url='.rawurlencode($url).'&amp;media='.rawurlencode($media).'" onclick="window.open(this.href, \'\', \'directories=no,location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no,width=750,height=316\');return false';
    }



    /**
     * Dumps a variable
     *
     * @param $var mixed Variable to dump
     * @param $pre bool  Whether to wrap the result in a HTML <pre> tag or not
     *
     * @return string Dumped variable
     */
    public static function dump($var, $pre = false)
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
     * @param $string string String to vulgarize
     *
     * @return string Vulgarized string
     */
    public static function vulgarize($string)
    {
        return trim(
            preg_replace(
                '~(-+)~',
                '-',
                preg_replace(
                    '~([^a-z0-9-]*)~',
                    '',
                    preg_replace(
                        '~((\s|\.|\')+)~',
                        '-',
                        html_entity_decode(
                            preg_replace(
                                '~&(a|o)elig;~',
                                '$1e',
                                preg_replace(
                                    '~&([a-z])(uml|acute|grave|circ|tilde|ring|cedil|slash);~',
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
     * Gets a recursive used traits list for a class
     *
     * @param $class string Class full name
     *
     * @return array Traits list
     */
    public static function getRecursiveTraits($class)
    {
        $reflection = new \ReflectionClass($class);
        $traits = array_keys($reflection->getTraits());

        foreach ($traits as $trait) {
            $traits = array_merge($traits, static::getRecursiveTraits($trait));
        }

        return $traits;
    }
}
