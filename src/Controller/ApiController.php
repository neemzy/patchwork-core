<?php

namespace Neemzy\Patchwork\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiController extends EntityController
{
    /**
     * Silex method that exposes routes to the app
     *
     * @param Silex\Application $app Application instance
     *
     * @return Silex\ControllerCollection Object encapsulating crafted routes
     */
    public function connect(Application $app, $table = null)
    {
        $ctrl = parent::connect($app);



        /**
         * List items
         */
        $ctrl
            ->get(
                '{uri}',
                function () use ($app) {
                    $response = new JsonResponse($app['serializer']->serialize($app['redbean']->findAll($this->table)));
                    $response->setEncodingOptions(JSON_NUMERIC_CHECK);

                    return $response;
                }
            )
            ->bind('api.'.$this->table.'.list')
            ->assert('uri', '.{0}')
            ->value('uri', '');



        /**
         * Read item
         */
        $ctrl
            ->get(
                '/{model}',
                function ($model) use ($app) {
                    if (0 == $model->id) {
                        $app->abort(JsonResponse::HTTP_NOT_FOUND);
                    }

                    $response = new JsonResponse($app['serializer']->serialize($model));
                    $response->setEncodingOptions(JSON_NUMERIC_CHECK);

                    return $response;
                }
            )
            ->bind('api.'.$this->table.'.read')
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
