<?php

namespace Patchwork\Controller;

use Symfony\Component\HttpFoundation\Response;
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
                '{uri}',
                function () use ($app) {
                    $data = R::findAndExport($this->class, 1);
                    return Tools::jsonResponse($data);
                }
            )
            ->bind('api.'.$this->class.'.list')
            ->assert('uri', '.{0}')
            ->value('uri', '');



        // Read item

        $ctrl
            ->get(
                '/{bean}',
                function ($bean) use ($app) {
                    if (! $data = R::findAndExport($this->class, 'id = ?', [$bean->id])) {
                        $app->abort(Response::HTTP_NOT_FOUND);
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
                    $code = $bean->id ? Response::HTTP_OK : Response::HTTP_CREATED;

                    try {
                        $bean->save();
                        $response = $bean->export();
                    } catch (Exception $e) {
                        $errors = $e->getDetails();
                        $response = ['errors' => []];
                        $code = Response::HTTP_BAD_REQUEST;

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
                        $app->abort(Response::HTTP_NOT_FOUND);
                    }

                    $bean->trash();
                    return Tools::jsonResponse(null, Response::HTTP_NO_CONTENT);
                }
            )
            ->bind('api.'.$this->class.'.delete')
            ->convert('bean', $this->beanProvider);



        // Gotta catch'em all

        $ctrl
            ->match(
                '{uri}',
                function () use ($app) {
                    $app->abort(Response::HTTP_BAD_REQUEST);
                }
            )
            ->assert('uri', '.*');



        return $ctrl;
    }
}
