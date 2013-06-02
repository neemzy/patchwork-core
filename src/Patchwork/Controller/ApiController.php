<?php

namespace Patchwork\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Patchwork\Helper\RedBean as R;
use Patchwork\Helper\Tools;

abstract class ApiController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        return $this->route($app);
    }



    protected function route($app, $class)
    {
        $ctrl = $app['controllers_factory'];



        // Get list

        $ctrl->get(
            '/',
            function () use ($app, $class) {
                $data = R::findAndExport($class, '1');
                return Tools::JSONResponse($data);
            }
        )->bind('api.'.$class.'.getAll');



        // Get item

        $ctrl->get(
            '/{id}',
            function ($id) use ($app, $class) {
                $data = R::findAndExport($class, 'id=?', array($id));
                return Tools::jsonResponse($data, (count($data) ? 200 : 404));
            }
        )->bind('api.'.$class.'.getOne')->assert('id', '\d+');



        return $ctrl;
    }
}
