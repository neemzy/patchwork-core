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
     * @param $message  string                                                    Exception message
     * @param $code     int                                                       Exit code
     * @param $previous Exception                                                 Parent exception
     * @param $details  array|Symfony\Component\Validator\ConstraintViolationList Details list
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
     * @param $details array|Symfony\Component\Validator\ConstraintViolationList details list
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
                $html .= '<li><b>'.$app['translator']->trans($error->getPropertyPath()).'</b> : '.$app['translator']->trans($error->getMessage()).'</li>';
            }

            $html .= '</ul>';
        }

        return $html;
    }
}
