<?php

namespace Patchwork\Helper;

class Exception extends \Exception
{
    private $details;



    public function __construct($message, $code = 0, \Exception $previous = null, $details = array())
    {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }



    public function getDetails()
    {
        return $this->details;
    }
}
