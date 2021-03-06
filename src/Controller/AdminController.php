<?php

namespace Neemzy\Patchwork\Controller;

use Silex\Application;

class AdminController extends EntityController
{
    /**
     * @var closure Authentication callback
     */
    protected $auth;



    /**
     * Silex method that exposes routes to the app
     * Attaches an authentication method to the controller
     *
     * @param Silex\Application $app Application instance
     *
     * @return Silex\ControllerCollection Object encapsulating crafted routes
     */
    public function connect(Application $app)
    {
        $ctrl = parent::connect($app);



        $this->auth = function () use ($app) {
            if (!$app['debug']) {
                $username = $app['request']->server->get('PHP_AUTH_USER', false);
                $password = $app['request']->server->get('PHP_AUTH_PW');

                if ((!$username || !$password) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_REMOTE_USER'], $matches)) {
                    list($username, $password) = explode(':', base64_decode($matches[1]));

                    $username = strip_tags($username);
                    $password = strip_tags($password);
                }

                if (($username != $app['config']['admin']['username']) || ($password != $app['config']['admin']['password'])) {
                    $response = new Response(null, Response::HTTP_UNAUTHORIZED);
                    $response->headers->set('WWW-Authenticate', 'Basic realm="Administration"');

                    return $response;
                }
            }
        };



        /**
         * List items
         */
        $ctrl
            ->get(
                '/list',
                function () use ($app) {
                    return $app['twig']->render(
                        'admin/'.$this->table.'/list.twig',
                        [$this->table.'s' => $app['redbean']->findAll(
                            $this->table,
                            'ORDER BY '.(array_key_exists('position', $app['redbean']->getColumns($this->table)) ? 'position' : 'id')
                        )]
                    );
                }
            )
            ->bind($this->table.'.list')
            ->before($this->auth);



        /**
         * Move item up or down
         */
        $ctrl
            ->get(
                '/move/{model}/{up}',
                function ($model, $up) use ($app) {
                    $model->move($up);
                    $app['redbean']->store($model);

                    return $app->redirect($app['url_generator']->generate($this->table.'.list'));
                }
            )
            ->bind($this->table.'.move')
            ->convert('model', $this->modelProvider)
            ->assert('up', '0|1')
            ->before($this->auth);



        /**
         * Clone item
         */
        $ctrl
            ->get(
                '/clone/{model}',
                function ($model) use ($app) {
                    $clone = $app['redbean']->dup($model->unbox());
                    $model->cloneFilesFor($clone);

                    $app['redbean']->store($clone);

                    $app['session']->getFlashBag()->clear();
                    $app['session']->getFlashBag()->add('success', $app['translator']->trans('success.cloning'));

                    return $app->redirect($app['url_generator']->generate($this->table.'.list'));
                }
            )
            ->bind($this->table.'.clone')
            ->convert('model', $this->modelProvider)
            ->before($this->auth);



        /**
         * Toggle item's visibility
         */
        $ctrl
            ->get(
                '/toggle/{model}',
                function ($model) use ($app) {
                    $model->toggle();
                    $app['redbean']->store($model);

                    return $app->redirect($app['url_generator']->generate($this->table.'.list'));
                }
            )
            ->bind($this->table.'.toggle')
            ->convert('model', $this->modelProvider)
            ->before($this->auth);



        /**
         * Delete item
         */
        $ctrl
            ->get(
                '/delete/{model}',
                function ($model) use ($app) {
                    $app['redbean']->trash($model);

                    $app['session']->getFlashBag()->clear();
                    $app['session']->getFlashBag()->add('success', $app['translator']->trans('success.deletion'));

                    return $app->redirect($app['url_generator']->generate($this->table.'.list'));
                }
            )
            ->bind($this->table.'.delete')
            ->convert('model', $this->modelProvider)
            ->before($this->auth);



        /**
         * Create/update item
         */
        $ctrl
            ->match(
                '/post/{model}',
                function ($model) use ($app) {
                    if ('POST' == $app['request']->getMethod()) {
                        $app['session']->getFlashBag()->clear();

                        $app['hydrator']->hydrate($model);
                        $errors = [];

                        // Format error array
                        foreach ($app['validator']->validate($model) as $error) {
                            $errors[] = [
                                $error->getPropertyPath() => $error->getMessage()
                            ];
                        }

                        if (!count($errors)) {
                            $app['redbean']->store($model);
                            $app['session']->getFlashBag()->add('success', $app['translator']->trans('success.save'));

                            return $app->redirect($app['url_generator']->generate($this->table.'.post', ['model' => $model->id]));
                        }

                        foreach ($errors as $error) {
                            $app['session']->getFlashBag()->add('error', $error);
                        }
                    }

                    return $app['twig']->render('admin/'.$this->table.'/post.twig', [$this->table => $model]);
                }
            )
            ->bind($this->table.'.post')
            ->convert('model', $this->modelProvider)
            ->value('model', 0)
            ->before($this->auth)
            ->method('GET|POST');



        /**
         * Delete item's attached file
         */
        $ctrl
            ->get(
                '/delete_file/{model}/{field}',
                function ($model, $field) use ($app) {
                    $app['session']->getFlashBag()->clear();

                    $file = $model->$field;
                    $model->$field = null;
                    $errors = [];

                    // Format error array
                    foreach ($app['validator']->validate($model) as $error) {
                        $errors[] = [
                            $error->getPropertyPath() => $error->getMessage()
                        ];
                    }

                    if (!count($errors)) {
                        is_file($file) && unlink($file);
                        $app['redbean']->store($model);
                        $app['session']->getFlashBag()->add('success', $app['translator']->trans('success.file_deletion'));
                    }

                    foreach ($errors as $error) {
                        $app['session']->getFlashBag()->add('error', $error);
                    }

                    return $app->redirect($app['url_generator']->generate($this->table.'.post', ['model' => $model->id]));
                }
            )
            ->bind($this->table.'.delete_file')
            ->convert('model', $this->modelProvider)
            ->before($this->auth);



        return $ctrl;
    }
}
