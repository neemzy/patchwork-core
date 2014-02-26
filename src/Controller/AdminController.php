<?php

namespace Patchwork\Controller;

use \RedBean_Facade as R;
use Patchwork\Exception;

class AdminController extends AbstractController
{
    protected function route($app, $class = null)
    {
        $ctrl = parent::route($app);
        
        if ($class) {
            $this->class = $class;
        }



        // List

        $ctrl
            ->get(
                '/list',
                function () use ($app) {
                    return $app['twig']->render(
                        'admin/'.$this->class.'/list.twig',
                        [$this->class.'s' => R::dispense($this->class)->getAll()]
                    );
                }
            )
            ->bind($this->class.'.list')
            ->before($this->auth);



        // Move

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



        // Clone

        $ctrl
            ->get(
                '/clone/{bean}',
                function ($bean) use ($app) {
                    $bean->dup();

                    $app['session']->getFlashBag()->clear();
                    $app['session']->getFlashBag()->set('message', 'La duplication a bien été effectuée');

                    return $app->redirect($app['url_generator']->generate($this->class.'.list'));
                }
            )
            ->bind($this->class.'.clone')
            ->convert('bean', $this->beanProvider)
            ->before($this->auth);



        // Toggle

        $ctrl
            ->get(
                '/toggle/{bean}',
                function ($bean) use ($app) {
                    $bean->toggle();

                    return $app->redirect($app['url_generator']->generate($this->class.'.list'));
                }
            )
            ->bind($this->class.'.toggle')
            ->convert('bean', $this->beanProvider)
            ->before($this->auth);



        // Delete

        $ctrl
            ->get(
                '/delete/{bean}',
                function ($bean) use ($app) {
                    R::trash($bean);

                    $app['session']->getFlashBag()->clear();
                    $app['session']->getFlashBag()->set('message', 'La suppression a bien été effectuée');

                    return $app->redirect($app['url_generator']->generate($this->class.'.list'));
                }
            )
            ->bind($this->class.'.delete')
            ->convert('bean', $this->beanProvider)
            ->before($this->auth);



        // Post

        $ctrl
            ->get(
                '/post/{bean}',
                function ($bean) use ($app) {
                    return $app['twig']->render('admin/'.$this->class.'/post.twig', [$this->class => $bean]);
                }
            )
            ->bind($this->class.'.post')
            ->convert('bean', $this->beanProvider)
            ->value('bean', 0)
            ->before($this->auth);



        // Submit

        $ctrl
            ->post(
                '/post/{bean}',
                function ($bean) use ($app) {
                    $new = !$bean->id;
                    $asserts = $bean->getAsserts($new);
                    
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

                        if ($new && $bean->id) {
                            return $app['twig']->render('admin/'.$this->class.'/post.twig', [$this->class => $bean]);
                        }
                    }

                    return $app->redirect($app['url_generator']->generate($this->class.'.post', ['bean' => $bean->id]));
                }
            )
            ->convert('bean', $this->beanProvider)
            ->value('bean', 0)
            ->before($this->auth);



        // Delete image

        $ctrl
            ->get(
                '/delete_image/{bean}',
                function ($bean) use ($app) {
                    $bean->deleteImage();

                    $app['session']->getFlashBag()->clear();
                    $app['session']->getFlashBag()->set('message', 'L\'image a bien été supprimée');
                    
                    return $app->redirect($app['url_generator']->generate($this->class.'.post', ['bean' => $bean->id]));
                }
            )
            ->bind($this->class.'.delete_image')
            ->convert('bean', $this->beanProvider)
            ->before($this->auth);

        

        return $ctrl;
    }
}
