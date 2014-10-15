<?php

namespace Patchwork\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;

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
     * @return Patchwork\Controller\ApiController
     */
    public static function getInstance($class, $readonly = false)
    {
        $instance = parent::getInstance($class);
        $instance->readonly = $readonly;

        return $instance;
    }



    /**
     * Silex method that exposes routes to the app
     *
     * @param Silex\Application $app   Application instance
     * @param string            $class Model unqualified classname
     *
     * @return Silex\ControllerCollection Object encapsulating crafted routes
     */
    public function connect(Application $app, $class = null)
    {
        $ctrl = parent::connect($app);

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
                    $response = new JsonResponse($app['redbean']->findAndExport($this->class, 1));
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
                '/{model}',
                function ($model) use ($app) {
                    if (!$data = $app['redbean']->findAndExport($this->class, 'id = ?', [$model->id])) {
                        $app->abort(JsonResponse::HTTP_NOT_FOUND);
                    }

                    $response = new JsonResponse($data);
                    $response->setEncodingOptions(JSON_NUMERIC_CHECK);

                    return $response;
                }
            )
            ->bind('api.'.$this->class.'.read')
            ->convert('model', $this->modelProvider);



        /**
         * Create/update item
         */
        $this->readonly || $ctrl
            ->match(
                '/{model}',
                function ($model) use ($app) {
                    $this->hydrate($model, $app['request']);
                    $errors = $this->validate($model, $app['validator']);

                    if (!count($errors)) {
                        $code = $model->id ? Response::HTTP_OK : Response::HTTP_CREATED;
                        $app['redbean']->store($model);
                        $data = $model->unbox()->export();
                    } else {
                        $code = Response::HTTP_BAD_REQUEST;
                        $data = $errors;
                    }

                    $response = new JsonResponse($data, $code);
                    $response->setEncodingOptions(JSON_NUMERIC_CHECK);

                    return $response;
                }
            )
            ->bind('api.'.$this->class.'.post')
            ->convert('model', $this->modelProvider)
            ->value('model', 0)
            ->method('POST|PUT');



        /**
         * Delete item
         */
        $this->readonly || $ctrl
            ->delete(
                '/{id}',
                function ($id) use ($app) {
                    $model = $app['redbean']->load($this->class, $id);

                    if (!$model->id) {
                        $app->abort(JsonResponse::HTTP_NOT_FOUND);
                    }

                    $app['redbean']->trash($model);

                    $response = new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
                    $response->setEncodingOptions(JSON_NUMERIC_CHECK);

                    return $response;
                }
            )
            ->bind('api.'.$this->class.'.delete')
            ->convert('model', $this->modelProvider);



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
