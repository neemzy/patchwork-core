<?php

namespace Patchwork\Controller;

use Silex\Application;
use Patchwork\Exception;
use Patchwork\Model\AbstractModel;

class AdminController extends AbstractController
{
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
                '/list',
                function () use ($app) {
                    return $app['twig']->render(
                        'admin/'.$this->class.'/list.twig',
                        [$this->class.'s' => $app['redbean']->findAll(
                            $this->class,
                            'ORDER BY '.call_user_func($app['config']['redbean_prefix'].mb_convert_case($this->class, MB_CASE_TITLE).'::orderBy')
                        )]
                    );
                }
            )
            ->bind($this->class.'.list')
            ->before($this->auth);



        /**
         * Move item up or down
         */
        $ctrl
            ->get(
                '/move/{bean}/{up}',
                function ($bean, $up) use ($app) {
                    $bean->move($up);

                    return $app->redirect($app['url_generator']->generate($this->class.'.list'));
                }
            )
            ->bind($this->class.'.move')
            ->convert('bean', $this->beanProvider)
            ->assert('up', '0|1')
            ->before($this->auth);



        /**
         * Clone item
         */
        $ctrl
            ->get(
                '/clone/{bean}',
                function ($bean) use ($app) {
                    $clone = $app['redbean']->dup($bean->unbox())->box();
                    $bean->cloneFilesFor($clone);

                    $app['redbean']->store($clone);

                    $app['session']->getFlashBag()->clear();
                    $app['session']->getFlashBag()->add('success', $app['translator']->trans('success.cloning'));

                    return $app->redirect($app['url_generator']->generate($this->class.'.list'));
                }
            )
            ->bind($this->class.'.clone')
            ->convert('bean', $this->beanProvider)
            ->before($this->auth);



        /**
         * Toggle item's visibility
         */
        $ctrl
            ->get(
                '/toggle/{bean}',
                function ($bean) use ($app) {
                    $bean->toggle();
                    $app['redbean']->store($bean);

                    return $app->redirect($app['url_generator']->generate($this->class.'.list'));
                }
            )
            ->bind($this->class.'.toggle')
            ->convert('bean', $this->beanProvider)
            ->before($this->auth);



        /**
         * Delete item
         */
        $ctrl
            ->get(
                '/delete/{bean}',
                function ($bean) use ($app) {
                    $app['redbean']->trash($bean);

                    $app['session']->getFlashBag()->clear();
                    $app['session']->getFlashBag()->add('success', $app['translator']->trans('success.deletion'));

                    return $app->redirect($app['url_generator']->generate($this->class.'.list'));
                }
            )
            ->bind($this->class.'.delete')
            ->convert('bean', $this->beanProvider)
            ->before($this->auth);



        /**
         * Create/update item
         */
        $ctrl
            ->match(
                '/post/{bean}',
                function ($bean) use ($app) {
                    if ('POST' == $app['request']->getMethod()) {
                        $app['session']->getFlashBag()->clear();

                        $this->hydrate($bean);
                        $errors = $this->validate($bean);

                        if (!count($errors)) {
                            $app['redbean']->store($bean);
                            $app['session']->getFlashBag()->add('success', $app['translator']->trans('success.save'));

                            return $app->redirect($app['url_generator']->generate($this->class.'.post', ['bean' => $bean->id]));
                        }

                        foreach ($errors as $error) {
                            $app['session']->getFlashBag()->add('error', $error);
                        }
                    }

                    return $app['twig']->render('admin/'.$this->class.'/post.twig', [$this->class => $bean]);
                }
            )
            ->bind($this->class.'.post')
            ->convert('bean', $this->beanProvider)
            ->value('bean', 0)
            ->before($this->auth)
            ->method('GET|POST');



        /**
         * Delete item's attached file
         */
        $ctrl
            ->get(
                '/delete_file/{bean}/{field}',
                function ($bean, $field) use ($app) {
                    $app['session']->getFlashBag()->clear();

                    $file = $bean->$field;
                    $bean->$field = null;
                    $errors = $this->validate($bean);

                    if (!count($errors)) {
                        is_file($file) && unlink($file);
                        $app['redbean']->store($bean);
                        $app['session']->getFlashBag()->add('success', $app['translator']->trans('success.file_deletion'));
                    }

                    foreach ($errors as $error) {
                        $app['session']->getFlashBag()->add('error', $error);
                    }

                    return $app->redirect($app['url_generator']->generate($this->class.'.post', ['bean' => $bean->id]));
                }
            )
            ->bind($this->class.'.delete_file')
            ->convert('bean', $this->beanProvider)
            ->before($this->auth);



        return $ctrl;
    }
}
