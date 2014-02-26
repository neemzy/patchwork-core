<?php

namespace Patchwork;

class Exception extends \Exception
{
    private $details;



    public function __construct($message, $code = 0, \Exception $previous = null, $details = [])
    {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }



    public function getDetails()
    {
        return $this->details;
    }



    public function getHTML()
    {
        $html = '<p>'.$this->getMessage().'</p>';

        if (count($errors = $this->getDetails())) {
            $app = App::getInstance();
            $html .= '<ul>';

            foreach ($errors as $error) {
                $html .= '<li><b>'.$app['translator']->trans($error->getPropertyPath()).'</b> : '.$app['translator']->trans($error->getMessage()).'</li>';
            }

            $html .= '</ul>';
        }

        return $html;
    }
}
