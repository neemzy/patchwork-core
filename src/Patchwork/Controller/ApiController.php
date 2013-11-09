<?php

namespace Patchwork\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Patchwork\Helper\RedBean as R;
use Patchwork\Helper\Tools;

class ApiController implements ControllerProviderInterface
{
    private $class;



    public static function getInstanceFor($class)
    {
        $instance = new self();
        $instance->class = $class;

        return $instance;
    }



    public function connect(Application $app)
    {
        return $this->route($app);
    }



    protected function route($app, $class = null)
    {
        $ctrl = $app['controllers_factory'];
        
        if ($class === null) {
            $class = $this->class;
        }



        // Get list

        $ctrl->get(
            '/',
            function () use ($app, $class) {
                $data = R::findAndExport($class, 1);
                return Tools::jsonResponse($data);
            }
        )->bind('api.'.$class.'.getAll');



        // Get item

        $ctrl->get(
            '/{id}',
            function ($id) use ($app, $class) {
                $code = !! ($data = R::findAndExport($class, 'id = ?', array($id)));
                return Tools::jsonResponse($data);
            }
        )->bind('api.'.$class.'.getOne')->assert('id', '\d+');



        return $ctrl;
    }
}
