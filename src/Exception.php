<?php

namespace Patchwork;

class Exception extends \Exception
{
    protected $details;



    public function __construct($message, $code = 0, \Exception $previous = null, $details = [])
    {
        parent::__construct($message, $code, $previous);

        $this->details = $details;
    }



    public function getDetails()
    {
        if (is_array($this->details)) {
            return $this->details;
        }

        return (array)$this->details->getIterator();
    }

    public function setDetails($details)
    {
        $this->details = $details;
    }



    public function getHTML()
    {
        $app = App::getInstance();
        $html = '<p>'.$app['translator']->trans($this->getMessage()).'</p>';

        if (count($errors = $this->getDetails())) {
            $html .= '<ul>';

            foreach ($errors as $error) {
                $html .= '<li><b>'.$app['translator']->trans($error->getPropertyPath()).'</b> : '.$app['translator']->trans($error->getMessage()).'</li>';
            }

            $html .= '</ul>';
        }

        return $html;
    }
}
