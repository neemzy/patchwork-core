<?php

namespace Patchwork;

class Tools
{
    /**
     * Dumps a variable
     *
     * @param mixed $var Variable to dump
     * @param bool  $pre Whether to wrap the result in a HTML <pre> tag or not
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
     * @param string $string String to vulgarize
     *
     * @return string Vulgarized string
     */
    public static function vulgarize($string)
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
     * Formats a date by the current locale
     *
     * @param string $date   Date in any standard format
     * @param string $format Format to apply
     *
     * @return string Formatted date
     */
    public static function localeDate($date, $format)
    {
        return strftime($format, strtotime($date));
    }



    /**
     * Gets a recursive used traits list for a class
     *
     * @param string $class Class full name
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

        if ($parent = $reflection->getParentClass()) {
            $traits = array_merge($traits, static::getRecursiveTraits($parent->getName()));
        }

        return $traits;
    }
}
