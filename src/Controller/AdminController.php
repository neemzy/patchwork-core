<?php

namespace Patchwork\Controller;

use Symfony\Component\Validator\Constraints as Assert;
use Patchwork\Helper\RedBean as R;
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

        $ctrl->get(
            '/list',
            function () use ($app, $class) {
                return $app['twig']->render(
                    'admin/'.$class.'/list.twig',
                    array($class.'s' => R::findAll($class, (R::typeHasField($class, 'position') ? 'ORDER BY position ASC' : '')))
                );
            }
        )->bind($class.'.list')->before($this->auth);



        // Move

        $ctrl->get(
            '/move/{id}/{up}',
            function ($id, $up) use ($app, $class) {
                $bean = R::load($class, $id);

                if ($bean->hasField('position')) {
                    if ($up && ($bean->position > 1)) {
                        $bean->position--;
                        R::exec('UPDATE '.$class.' SET position = position + 1 WHERE position = ?', array($bean->position));
                    } else if ((! $up) && ($bean->position < R::count($class))) {
                        $bean->position++;
                        R::exec('UPDATE '.$class.' SET position = position - 1 WHERE position = ?', array($bean->position));
                    }

                    R::store($bean);
                }
                
                return $app->redirect($app['url_generator']->generate($class.'.list'));
            }
        )->bind($class.'.move')->assert('id', '\d+')->assert('up', '0|1')->before($this->auth);



        // Clone

        $ctrl->get(
            '/clone/{id}',
            function ($id) use ($app, $class) {
                $bean = R::load($class, $id);
                $clone = R::dup($bean);
                R::store($clone);

                // Image cloning
                if ($bean->hasField('image') && $bean->image) {
                    $clone->image = $clone->id.'.'.array_pop(explode('.', $bean->image));
                    R::store($clone);

                    $dir = $bean->getImageDir();
                    copy($dir.$bean->image, $dir.$clone->image);
                }

                $app['session']->getFlashBag()->clear();
                $app['session']->getFlashBag()->set('message', 'La duplication a bien été effectuée');

                return $app->redirect($app['url_generator']->generate($class.'.list'));
            }
        )->bind($class.'.clone')->assert('id', '\d+')->before($this->auth);



        // Toggle

        $ctrl->get(
            '/toggle/{id}',
            function ($id) use ($app, $class) {
                $bean = R::load($class, $id);

                if ($bean->hasField('active')) {
                    $bean->active = !$bean->active;
                    R::store($bean);
                }

                return $app->redirect($app['url_generator']->generate($class.'.list'));
            }
        )->bind($class.'.toggle')->assert('id', '\d+')->before($this->auth);



        // Delete

        $ctrl->get(
            '/delete/{id}',
            function ($id) use ($app, $class) {
                $bean = R::load($class, $id);

                // Image deletion
                if ($bean->hasField('image') && $bean->image) {
                    unlink($bean->getImageDir().$bean->image);
                }

                R::trash($bean);

                $app['session']->getFlashBag()->clear();
                $app['session']->getFlashBag()->set('message', 'La suppression a bien été effectuée');

                return $app->redirect($app['url_generator']->generate($class.'.list'));
            }
        )->bind($class.'.delete')->assert('id', '\d+')->before($this->auth);



        // Post

        $ctrl->get(
            '/post/{id}',
            function ($id) use ($app, $class) {
                return $app['twig']->render('admin/'.$class.'/post.twig', array($class => R::load($class, $id)));
            }
        )->bind($class.'.post')->assert('id', '\d+')->value('id', 0)->before($this->auth);



        // Submit

        $ctrl->post(
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

                    // Image upload
                    if ($bean->hasField('image') && $app['request']->files->has('image') && ($image = $app['request']->files->get('image'))) {
                        if ($error = $image->getError()) {
                            $message = 'Une erreur est survenue lors de l\'envoi du fichier';

                            switch ($error) {
                                case UPLOAD_ERR_INI_SIZE:
                                case UPLOAD_ERR_FORM_SIZE:
                                    $message = 'Le fichier sélectionné est trop lourd';
                                    break;
                            }
                        } else if (! in_array($extension = strtolower($image->guessExtension()), array('jpeg', 'png', 'gif'))) {
                            $message = 'Seuls les formats JPEG, PNG et GIF sont autorisés';
                        }

                        if (isset($message)) {
                            throw new Exception($message);
                        }

                        $dir = $bean->getImageDir();
                        $file = $bean->id.'.'.$extension;

                        if ($bean->image) {
                            unlink($dir.$bean->image);
                        }

                        $image->move($dir, $file);
                        $bean->setImage($file);

                        R::store($bean);
                    }

                    $app['session']->getFlashBag()->set('message', 'L\'enregistrement a bien été effectué');
                } catch (Exception $e) {
                    $app['session']->getFlashBag()->set('error', true);
                    $message = '<p>'.$e->getMessage().'</p>';

                    if (count($errors = $e->getDetails())) {
                        $message .= '<ul>';

                        foreach ($errors as $error) {
                            $message .= '<li><strong>'.$app['translator']->trans($error->getPropertyPath()).'</strong> : '.$app['translator']->trans($error->getMessage()).'</li>';
                        }

                        $message .= '</ul>';
                    }

                    $app['session']->getFlashBag()->set('message', $message);

                    if (! $bean->id) {
                        return $app['twig']->render('admin/'.$class.'/post.twig', array($class => $bean));
                    }
                }

                return $app->redirect($app['url_generator']->generate($class.'.post', array('id' => $bean->id)));
            }
        )->assert('id', '\d+')->value('id', 0)->before($this->auth);



        // Image delete

        $ctrl->get(
            '/delete_image/{id}',
            function ($id) use ($app, $class) {
                $bean = R::load($class, $id);

                if ($bean->hasField('image') && $bean->image) {
                    unlink($bean->getImageDir().$bean->image);
                    $bean->image = null;

                    R::store($bean);

                    $app['session']->getFlashBag()->clear();
                    $app['session']->getFlashBag()->set('message', 'L\'image a bien été supprimée');
                }
                
                return $app->redirect($app['url_generator']->generate($class.'.post', array('id' => $id)));
            }
        )->bind($class.'.delete_image')->assert('id', '\d+')->before($this->auth);

        

        return $ctrl;
    }
}
