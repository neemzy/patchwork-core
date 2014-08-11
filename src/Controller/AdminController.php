<?php

namespace Patchwork\Controller;

use Silex\Application;
use \RedBean_Facade as R;
use Patchwork\Exception;
use Patchwork\Model\AbstractModel;

class AdminController extends AbstractController
{
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
                '/list',
                function () use ($app) {
                    return $app['twig']->render(
                        'admin/'.$this->class.'/list.twig',
                        [$this->class.'s' => call_user_func(AbstractModel::qualify($this->class, 'getAll'))]
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
                    $bean->dup();

                    $app['session']->getFlashBag()->clear();
                    $app['session']->getFlashBag()->set('message', $app['translator']->trans('Cloning successful.'));

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
                    $bean->save();

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
                    $bean->trash();

                    $app['session']->getFlashBag()->clear();
                    $app['session']->getFlashBag()->set('message', $app['translator']->trans('Deletion successful.'));

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
                        $redirect = true;
                        $bean->hydrate();

                        $app['session']->getFlashBag()->clear();

                        try {
                            $bean->save();

                            $app['session']->getFlashBag()->set('message', $app['translator']->trans('Save successful.'));
                        } catch (Exception $e) {
                            $app['session']->getFlashBag()->set('error', true);
                            $app['session']->getFlashBag()->set('message', $e->getHTML());

                            $redirect = false;
                        }

                        if ($redirect) {
                            return $app->redirect($app['url_generator']->generate($this->class.'.post', ['bean' => $bean->id]));
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
                '/delete_file/{bean}/{key}',
                function ($bean, $key) use ($app) {
                    $app['session']->getFlashBag()->clear();

                    try {
                        $bean->deleteFile($key, true);

                        $app['session']->getFlashBag()->set('message', $app['translator']->trans('File deletion successful.'));
                    } catch (Exception $e) {
                        $app['session']->getFlashBag()->set('error', true);
                        $app['session']->getFlashBag()->set('message', $e->getHTML());
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
