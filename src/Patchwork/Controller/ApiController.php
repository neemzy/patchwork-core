<?php

namespace Patchwork\Controller;

use Patchwork\Helper\RedBean as R;
use Patchwork\Helper\Exception;
use Patchwork\Helper\Tools;

class ApiController extends AbstractController
{
    protected $class;
    protected $readonly;



    public static function getInstanceFor($class, $readonly = false)
    {
        $instance = parent::getInstanceFor($class);
        $instance->readonly = $readonly;

        return $instance;
    }



    protected function route($app, $class = null)
    {
        $ctrl = parent::route($app);
        
        if ($class === null) {
            $class = $this->class;
        }



        // Read list

        $ctrl->get(
            '/',
            function () use ($app, $class) {
                $data = R::findAndExport($class, 1);
                return Tools::jsonResponse($data);
            }
        )->bind('api.'.$class.'.list');



        // Read item

        $ctrl->get(
            '/{id}',
            function ($id) use ($app, $class) {
                if (! $data = R::findAndExport($class, 'id = ?', array($id))) {
                    $app->abort(404);
                }

                return Tools::jsonResponse($data);
            }
        )->bind('api.'.$class.'.read')->assert('id', '\d+');



        // Create/Update

        $this->readonly && $ctrl->match(
            '/{id}',
            function ($id) use ($app, $class) {
                $id = +$id;
                $bean = R::load($class, $id);

                if ($id != +$bean_id) {
                    $app->abort(404);
                }

                $asserts = $bean->getAsserts();
                
                foreach ($asserts as $key => $assert) {
                    $bean->$key = $app['request']->get($key);
                }

                if ((R::typeHasField($class, 'position')) && (! $id)) {
                    $position = R::getCell('SELECT position FROM '.$class.' ORDER BY position DESC LIMIT 1');
                    $bean->position = $position + 1;
                }

                $code = $id ? 200 : 201;

                try {
                    R::store($bean);
                    $response = $bean->export();
                } catch (Exception $e) {
                    $errors = $e->getDetails();
                    $response = array('errors' => array());
                    $code = 400;

                    foreach ($errors as $error) {
                        $response['errors'][$error->getPropertyPath()] = $app['translator']->trans($error->getMessage());
                    }
                }

                return Tools::jsonResponse($response, $code);
            }
        )->bind('api.'.$class.'.post')->assert('id', '\d*')->method('POST|PUT');



        // Delete

        $this->readonly && $ctrl->delete(
            '/{id}',
            function ($id) use ($app, $class) {
                $bean = R::load($class, $id);

                if (! $bean->id) {
                    $app->abort(404);
                }

                if (R::typeHasField($class, 'position')) {
                    R::exec('UPDATE '.$class.' SET position = position - 1 WHERE position > ?', array($bean->position));
                }

                if (R::typeHasField($class, 'image')) {
                    $dir = BASE_PATH.'/public/assets/img/'.$class.'/';
                    unlink($dir.$bean->image);
                }

                R::trash($bean);

                return Tools::jsonResponse(null, 204);
            }
        )->bind('api.'.$class.'.delete')->assert('id', '\d+');



        // Gotta catch'em all

        $ctrl->match(
            '{uri}',
            function () use ($app) {
                $app->abort(400);
            }
        )->assert('uri', '.*');



        return $ctrl;
    }
}
