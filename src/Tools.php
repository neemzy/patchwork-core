<?php

namespace Patchwork;

use Symfony\Component\HttpFoundation\JsonResponse;

class Tools
{
    public static function qualify($class)
    {
        return REDBEAN_MODEL_PREFIX.mb_convert_case($class, MB_CASE_TITLE);
    }



    public static function jsonResponse($data, $code = JsonResponse::HTTP_OK, $options = JSON_NUMERIC_CHECK)
    {
        $response = new JsonResponse();
        $response->setData($data);
        $response->setStatusCode($code);
        $response->setEncodingOptions($options);

        return $response;
    }


    
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



    public static function vulgarize($s)
    {
        return trim(preg_replace('~(-+)~', '-', preg_replace('~([^a-z0-9-]*)~', '', preg_replace('~((\s|\.|\')+)~', '-', html_entity_decode(preg_replace('~&(a|o)elig;~', '$1e', preg_replace('~&([a-z])(uml|acute|grave|circ|tilde|ring|cedil|slash);~', '$1', strtolower(htmlentities($s, ENT_COMPAT, 'utf-8')))), ENT_COMPAT, 'utf-8')))), '-');
    }



    public static function dump($var, $pre_wrap = false)
    {
        ob_start();
        var_dump($var);
        $dump = ob_get_clean();

        $pre_wrap && $dump = '<pre class="debug">'.$dump.'</pre>';
        return $dump;
    }
}
