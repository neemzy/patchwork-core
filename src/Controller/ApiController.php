<?php

namespace Patchwork\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use \RedBean_Facade as R;
use Patchwork\Exception;
use Patchwork\Tools;

class ApiController extends AbstractController
{
    /**
     * @var bool Route attribution flag
     */
    protected $readonly;



    /**
     * Maps an instance of this controller to a model
     *
     * @param string $class Model unqualified classname
     *
     * @return Patchwork\Controller\ApiController Mapped instance
     */
    public static function getInstanceFor($class, $readonly = false)
    {
        $instance = parent::getInstanceFor($class);
        $instance->readonly = $readonly;

        return $instance;
    }



    /**
     * Crafts routes for this instance
     *
     * @param Silex\Application $app   Application instance
     * @param string            $class Model unqualified classname
     *
     * @return Silex\ControllerCollection Object encapsulating crafted routes
     */
    protected function route(Application $app, $class = null)
    {
        $ctrl = parent::route($app);
        
        if ($class) {
            $this->class = $class;
        }



        /**
         * List items
         */
        $ctrl
            ->get(
                '{uri}',
                function () use ($app) {
                    $response = new JsonResponse(R::findAndExport($this->class, 1));
                    $response->setEncodingOptions(JSON_NUMERIC_CHECK);

                    return $response;
                }
            )
            ->bind('api.'.$this->class.'.list')
            ->assert('uri', '.{0}')
            ->value('uri', '');



        /**
         * Read item
         */
        $ctrl
            ->get(
                '/{bean}',
                function ($bean) use ($app) {
                    if (!$data = R::findAndExport($this->class, 'id = ?', [$bean->id])) {
                        $app->abort(JsonResponse::HTTP_NOT_FOUND);
                    }

                    $response = new JsonResponse($data);
                    $response->setEncodingOptions(JSON_NUMERIC_CHECK);

                    return $response;
                }
            )
            ->bind('api.'.$this->class.'.read')
            ->convert('bean', $this->beanProvider);



        /**
         * Create/update item
         */
        $this->readonly || $ctrl
            ->match(
                '/{bean}',
                function ($bean) use ($app) {
                    $bean->hydrate();
                    $code = $bean->id ? Response::HTTP_OK : Response::HTTP_CREATED;

                    try {
                        $bean->save();
                        $data = $bean->export();
                    } catch (Exception $e) {
                        $errors = $e->getDetails();
                        $data = ['errors' => []];
                        $code = Response::HTTP_BAD_REQUEST;

                        foreach ($errors as $error) {
                            $data['errors'][$error->getPropertyPath()] = $app['translator']->trans($error->getMessage());
                        }
                    }

                    $response = new JsonResponse($data, $code);
                    $response->setEncodingOptions(JSON_NUMERIC_CHECK);

                    return $response;
                }
            )
            ->bind('api.'.$this->class.'.post')
            ->convert('bean', $this->beanProvider)
            ->value('bean', 0)
            ->method('POST|PUT');



        /**
         * Delete item
         */
        $this->readonly || $ctrl
            ->delete(
                '/{id}',
                function ($id) use ($app) {
                    $bean = R::load($this->class, $id);

                    if (! $bean->id) {
                        $app->abort(JsonResponse::HTTP_NOT_FOUND);
                    }

                    $bean->trash();

                    $response = new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
                    $response->setEncodingOptions(JSON_NUMERIC_CHECK);

                    return $response;
                }
            )
            ->bind('api.'.$this->class.'.delete')
            ->convert('bean', $this->beanProvider);



        /**
         * Catch-all
         */
        $ctrl
            ->match(
                '{uri}',
                function () use ($app) {
                    $app->abort(JsonResponse::HTTP_BAD_REQUEST);
                }
            )
            ->assert('uri', '.*');



        return $ctrl;
    }
}
