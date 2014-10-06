<?php

namespace Patchwork;

class Exception extends \Exception
{
    /**
     * @var array|Symfony\Component\Validator\ConstraintViolationList Details list
     */
    protected $details;



    /**
     * Constructor
     *
     * @param string                                                    $message  Exception message
     * @param int                                                       $code     Exit code
     * @param Exception                                                 $previous Parent exception
     * @param array|Symfony\Component\Validator\ConstraintViolationList $details  Details list
     *
     * @return void
     */
    public function __construct($message, $code = 0, \Exception $previous = null, $details = [])
    {
        parent::__construct($message, $code, $previous);

        $this->details = $details;
    }



    /**
     * Gets the details list
     *
     * @return array Details list
     */
    public function getDetails()
    {
        if (is_array($this->details)) {
            return $this->details;
        }

        return (array)$this->details->getIterator();
    }



    /**
     * Sets the details list
     *
     * @param array|Symfony\Component\Validator\ConstraintViolationList $details details list
     *
     * @return void
     */
    public function setDetails($details)
    {
        $this->details = $details;
    }



    /**
     * Displays the message and details list as HTML
     *
     * @return string Generated HTML
     */
    public function getHTML()
    {
        $app = App::getInstance();
        $html = '<p>'.$app['translator']->trans($this->getMessage()).'</p>';

        if (count($errors = $this->getDetails())) {
            $html .= '<ul>';

            foreach ($errors as $error) {
                $html .= '<li><b>'.$app['translator']->trans($error->getPropertyPath()).'</b> : '.$error->getMessage().'</li>';
            }

            $html .= '</ul>';
        }

        return $html;
    }



    /**
     * Displays the message and details list as JSON
     *
     * @return array Generated JSON-ready array
     */
    public function getJSON()
    {
        $app = App::getInstance();
        $data = ['message' => $app['translator']->trans($this->getMessage())];

        if (count($errors = $this->getDetails())) {
            $data['details'] = [];

            foreach ($errors as $error) {
                $data['details'][$app['translator']->trans($error->getPropertyPath())] = $app['translator']->trans($error->getMessage());
            }
        }

        return $data;
    }
}
