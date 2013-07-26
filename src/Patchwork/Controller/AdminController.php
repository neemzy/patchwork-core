<?php

namespace Patchwork\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use PHPImageWorkshop\ImageWorkshop;
use Patchwork\Helper\RedBean as R;
use Patchwork\Helper\Exception;

class AdminController implements ControllerProviderInterface
{
    private $class;



    public static function getInstanceFor($class)
    {
        $instance = new self();
        $instance->class = $class;

        return $instance;
    }



    public function connect(Application $app)
    {
        return $this->route(
            $app,
            function () use ($app) {
                $username = $app['request']->server->get('PHP_AUTH_USER', false);
                $password = $app['request']->server->get('PHP_AUTH_PW');

                if ((! $username || ! $password) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_REMOTE_USER'], $matches)) {
                    list($username, $password) = explode(':', base64_decode($matches[1]));
                    $username = strip_tags($username);
                    $password = strip_tags($password);
                }

                if (($username != BO_USER) || ($password != BO_PASS)) {
                    $response = new Response();
                    $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', 'Administration'));
                    $response->setStatusCode(401, 'Please sign in.');
                    return $response;
                }
            }
        );
    }



    protected function route($app, $auth, $class = null)
    {
        $ctrl = $app['controllers_factory'];
        
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
        )->bind($class.'.list')->before($auth);



        // Move

        $ctrl->get(
            '/move/{id}/{up}',
            function ($id, $up) use ($app, $class) {
                $bean = R::load($class, $id);

                if (($up) && ($bean->position > 1)) {
                    $bean->position--;
                    R::exec('UPDATE '.$class.' SET position = position + 1 WHERE position = ?', array($bean->position));
                } else if ((! $up) && ($bean->position < R::count($class))) {
                    $bean->position++;
                    R::exec('UPDATE '.$class.' SET position = position - 1 WHERE position = ?', array($bean->position));
                }

                R::store($bean);
                return $app->redirect($app['url_generator']->generate($class.'.list'));
            }
        )->bind($class.'.move')->assert('id', '\d+')->assert('up', '0|1')->before($auth);



        // Clone

        $ctrl->get(
            '/clone/{id}',
            function ($id) use ($app, $class) {
                $app['session']->getFlashBag()->clear();
                $app['session']->getFlashBag()->set('message', 'La duplication a bien été effectuée');

                $bean = R::load($class, $id);
                $clone = R::dup($bean);

                if (R::typeHasField($class, 'position')) {
                    $position = R::getCell('SELECT position FROM '.$class.' ORDER BY position DESC LIMIT 1');
                    $clone->position = $position + 1;
                }

                R::store($clone);

                if ((R::typeHasField($class, 'image')) && ($bean->image)) {
                    $dir = BASE_PATH.'/public/assets/img/'.$class.'/';
                    $clone->image = $clone->id.'.'.array_pop(explode('.', $bean->image));
                    R::store($clone);
                    copy($dir.$bean->image, $dir.$clone->image);
                }

                return $app->redirect($app['url_generator']->generate($class.'.list'));
            }
        )->bind($class.'.clone')->assert('id', '\d+')->before($auth);



        // Toggle

        $ctrl->get(
            '/toggle/{id}',
            function ($id) use ($app, $class) {
                $bean = R::load($class, $id);
                $bean->active = !$bean->active;
                R::store($bean);

                return $app->redirect($app['url_generator']->generate($class.'.list'));
            }
        )->bind($class.'.toggle')->assert('id', '\d+')->before($auth);



        // Delete

        $ctrl->get(
            '/delete/{id}',
            function ($id) use ($app, $class) {
                $app['session']->getFlashBag()->clear();
                $app['session']->getFlashBag()->set('message', 'La suppression a bien été effectuée');

                $bean = R::load($class, $id);

                if (R::typeHasField($class, 'position')) {
                    R::exec('UPDATE '.$class.' SET position = position - 1 WHERE position > ?', array($bean->position));
                }

                if (R::typeHasField($class, 'image')) {
                    $dir = BASE_PATH.'/public/assets/img/'.$class.'/';
                    unlink($dir.$bean->image);
                }

                R::trash($bean);
                return $app->redirect($app['url_generator']->generate($class.'.list'));
            }
        )->bind($class.'.delete')->assert('id', '\d+')->before($auth);



        // Post

        $ctrl->get(
            '/post/{id}',
            function ($id) use ($app, $class) {
                return $app['twig']->render('admin/'.$class.'/post.twig', array($class => R::load($class, $id)));
            }
        )->bind($class.'.post')->assert('id', '\d+')->value('id', 0)->before($auth);



        // Submit

        $ctrl->post(
            '/post/{id}',
            function (Request $request, $id) use ($app, $class) {
                $app['session']->getFlashBag()->clear();
                $app['session']->getFlashBag()->set('message', 'L\'enregistrement a bien été effectué');

                $bean = R::load($class, $id);
                $asserts = $bean->getAsserts();
                
                foreach ($asserts as $key => $assert) {
                    $bean->$key = $request->get($key);
                }

                if ((R::typeHasField($class, 'position')) && (! $id)) {
                    $position = R::getCell('SELECT position FROM '.$class.' ORDER BY position DESC LIMIT 1');
                    $bean->position = $position + 1;
                }

                try {
                    $bean->bindApp($app);
                    R::store($bean);
                } catch (Exception $e) {
                    $app['session']->getFlashBag()->set('error', true);
                    $message = '<p>'.$e->getMessage().'</p><ul>';
                    $errors = $e->getDetails();

                    foreach ($errors as $error) {
                        $message .= '<li><strong>'.$app['translator']->trans($error->getPropertyPath()).'</strong> : '.$app['translator']->trans($error->getMessage()).'</li>';
                    }

                    $message .= '</ul>';
                    $app['session']->getFlashBag()->set('message', $message);

                    return $app['twig']->render('admin/'.$class.'/post.twig', array($class => $bean));
                }

                if ((R::typeHasField($class, 'image')) && ($request->files->has('image')) && ($image = $request->files->get('image'))) {
                    if ($error = $image->getError()) {
                        $message = 'Une erreur est survenue lors de l\'envoi du fichier';

                        switch ($error) {
                            case UPLOAD_ERR_INI_SIZE:
                            case UPLOAD_ERR_FORM_SIZE:
                                $message = 'Le fichier sélectionné est trop lourd';
                                break;
                        }

                        $app['session']->getFlashBag()->set('error', true);
                        $app['session']->getFlashBag()->set('message', $message);
                    } else {
                        if (! in_array($extension = strtolower($image->guessExtension()), array('jpeg', 'png', 'gif'))) {
                            $app['session']->getFlashBag()->set('error', true);
                            $app['session']->getFlashBag()->set('message', 'Seuls les formats JPEG, PNG et GIF sont autorisés');
                        } else {
                            $dir = BASE_PATH.'/public/assets/img/'.$class.'/';
                            $file = $bean->id.'.'.$extension;

                            if ($bean->image) {
                                unlink($dir.$bean->image);
                            }

                            $image->move($dir, $file);
                            $bean->setImage($dir, $file);
                        }
                    }
                }

                return $app->redirect($app['url_generator']->generate($class.'.post', array('id' => $bean->id)));
            }
        )->assert('id', '\d+')->value('id', 0)->before($auth);



        // Image delete

        $ctrl->get(
            '/delete_image/{id}',
            function ($id) use ($app, $class) {
                $app['session']->getFlashBag()->clear();
                $app['session']->getFlashBag()->set('message', 'L\'image a bien été supprimée');

                $bean = R::load($class, $id);
                $dir = BASE_PATH.'/public/assets/img/'.$class.'/';
                unlink($dir.$bean->image);
                $bean->image = null;
                R::store($bean);

                return $app->redirect($app['url_generator']->generate($class.'.post', array('id' => $id)));
            }
        )->bind($class.'.delete_image')->assert('id', '\d+')->before($auth);

        

        return $ctrl;
    }
}
