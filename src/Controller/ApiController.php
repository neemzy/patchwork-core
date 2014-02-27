<?php

namespace Patchwork\Controller;

use \RedBean_Facade as R;
use Patchwork\Exception;
use Patchwork\Tools;

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
        
        if ($class) {
            $this->class = $class;
        }



        // Read list

        $ctrl
            ->get(
                '/',
                function () use ($app) {
                    $data = R::findAndExport($this->class, 1);
                    return Tools::jsonResponse($data);
                }
            )
            ->bind('api.'.$this->class.'.list');



        // Read item

        $ctrl
            ->get(
                '/{bean}',
                function ($bean) use ($app) {
                    if (! $data = R::findAndExport($this->class, 'id = ?', [$bean->id])) {
                        $app->abort(404);
                    }

                    return Tools::jsonResponse($data);
                }
            )
            ->bind('api.'.$this->class.'.read')
            ->convert('bean', $this->beanProvider);



        // Create/Update

        $this->readonly || $ctrl
            ->match(
                '/{bean}',
                function ($bean) use ($app) {
                    $bean->hydrate();
                    $code = $bean->id ? 200 : 201;

                    try {
                        $bean->save();
                        $response = $bean->export();
                    } catch (Exception $e) {
                        $errors = $e->getDetails();
                        $response = ['errors' => []];
                        $code = 400;

                        foreach ($errors as $error) {
                            $response['errors'][$error->getPropertyPath()] = $app['translator']->trans($error->getMessage());
                        }
                    }

                    return Tools::jsonResponse($response, $code);
                }
            )
            ->bind('api.'.$this->class.'.post')
            ->convert('bean', $this->beanProvider)
            ->value('bean', 0)
            ->method('POST|PUT');



        // Delete

        $this->readonly || $ctrl
            ->delete(
                '/{id}',
                function ($id) use ($app) {
                    $bean = R::load($this->class, $id);

                    if (! $bean->id) {
                        $app->abort(404);
                    }

                    $bean->trash();
                    return Tools::jsonResponse(null, 204);
                }
            )
            ->bind('api.'.$this->class.'.delete')
            ->convert('bean', $this->beanProvider);



        // Gotta catch'em all

        $ctrl
            ->match(
                '{uri}',
                function () use ($app) {
                    $app->abort(400);
                }
            )
            ->assert('uri', '.*');



        return $ctrl;
    }
}
