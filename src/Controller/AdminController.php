<?php

namespace Patchwork\Controller;

use \RedBean_Facade as R;
use Patchwork\Helper\Exception;

class AdminController extends AbstractController
{
    protected function route($app, $class = null)
    {
        $ctrl = parent::route($app);
        
        if ($class === null) {
            $class = $this->class;
        }



        // List

        $ctrl
            ->get(
                '/list',
                function () use ($app, $class) {
                    return $app['twig']->render(
                        'admin/'.$class.'/list.twig',
                        array($class.'s' => R::dispense($class)->getAll())
                    );
                }
            )
            ->bind($class.'.list')
            ->before($this->auth);



        // Move

        $ctrl
            ->get(
                '/move/{id}/{up}',
                function ($id, $up) use ($app, $class) {
                    $bean = R::load($class, $id);
                    $bean->move($up);
                    
                    return $app->redirect($app['url_generator']->generate($class.'.list'));
                }
            )
            ->bind($class.'.move')
            ->assert('id', '\d+')
            ->assert('up', '0|1')
            ->before($this->auth);



        // Clone

        $ctrl
            ->get(
                '/clone/{id}',
                function ($id) use ($app, $class) {
                    $bean = R::load($class, $id);
                    $bean->dup();

                    $app['session']->getFlashBag()->clear();
                    $app['session']->getFlashBag()->set('message', 'La duplication a bien été effectuée');

                    return $app->redirect($app['url_generator']->generate($class.'.list'));
                }
            )
            ->bind($class.'.clone')
            ->assert('id', '\d+')
            ->before($this->auth);



        // Toggle

        $ctrl
            ->get(
                '/toggle/{id}',
                function ($id) use ($app, $class) {
                    $bean = R::load($class, $id);
                    $bean->toggle();

                    return $app->redirect($app['url_generator']->generate($class.'.list'));
                }
            )
            ->bind($class.'.toggle')
            ->assert('id', '\d+')
            ->before($this->auth);



        // Delete

        $ctrl
            ->get(
                '/delete/{id}',
                function ($id) use ($app, $class) {
                    $bean = R::load($class, $id);
                    R::trash($bean);

                    $app['session']->getFlashBag()->clear();
                    $app['session']->getFlashBag()->set('message', 'La suppression a bien été effectuée');

                    return $app->redirect($app['url_generator']->generate($class.'.list'));
                }
            )
            ->bind($class.'.delete')
            ->assert('id', '\d+')
            ->before($this->auth);



        // Post

        $ctrl
            ->get(
                '/post/{id}',
                function ($id) use ($app, $class) {
                    return $app['twig']->render('admin/'.$class.'/post.twig', array($class => R::load($class, $id)));
                }
            )
            ->bind($class.'.post')
            ->assert('id', '\d+')
            ->value('id', 0)
            ->before($this->auth);



        // Submit

        $ctrl
            ->post(
                '/post/{id}',
                function ($id) use ($app, $class) {
                    $bean = R::load($class, $id);
                    $asserts = $bean->getAsserts(! $bean->id);
                    
                    foreach ($asserts as $key => $assert) {
                        $bean->$key = $app['request']->get($key);
                    }

                    $app['session']->getFlashBag()->clear();

                    try {
                        R::store($bean);
                        $app['session']->getFlashBag()->set('message', 'L\'enregistrement a bien été effectué');
                    } catch (Exception $e) {
                        $app['session']->getFlashBag()->set('error', true);
                        $app['session']->getFlashBag()->set('message', $e->getHTML());

                        if ($bean->id == $id) {
                            return $app['twig']->render('admin/'.$class.'/post.twig', array($class => $bean));
                        }
                    }

                    return $app->redirect($app['url_generator']->generate($class.'.post', array('id' => $bean->id)));
                }
            )
            ->assert('id', '\d+')
            ->value('id', 0)
            ->before($this->auth);



        // Delete image

        $ctrl
            ->get(
                '/delete_image/{id}',
                function ($id) use ($app, $class) {
                    $bean = R::load($class, $id);
                    $bean->deleteImage();

                    $app['session']->getFlashBag()->clear();
                    $app['session']->getFlashBag()->set('message', 'L\'image a bien été supprimée');
                    
                    return $app->redirect($app['url_generator']->generate($class.'.post', array('id' => $id)));
                }
            )
            ->bind($class.'.delete_image')
            ->assert('id', '\d+')
            ->before($this->auth);

        

        return $ctrl;
    }
}
