<?php

namespace Patchwork\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use \RedBean_Facade as R;

abstract class AbstractController implements ControllerProviderInterface
{
    protected $class;
    protected $auth;
    protected $beanProvider;



    /**
     * Constructor
     * Attaches a model provider and an authentication method to the controller
     *
     * @return void
     */
    public function __construct()
    {
        $this->auth = function () {
            $app = App::getInstance();

            if (! $app['debug']) {
                $username = $app['request']->server->get('PHP_AUTH_USER', false);
                $password = $app['request']->server->get('PHP_AUTH_PW');

                if ((! $username || ! $password) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_REMOTE_USER'], $matches)) {
                    list($username, $password) = explode(':', base64_decode($matches[1]));

                    $username = strip_tags($username);
                    $password = strip_tags($password);
                }

                if (($username != ADMIN_USER) || ($password != ADMIN_PASS)) {
                    $response = new Response(null, Response::HTTP_UNAUTHORIZED);
                    $response->headers->set('WWW-Authenticate', 'Basic realm="Administration"');

                    return $response;
                }
            }
        };

        $this->beanProvider = function ($id) {
            return R::load($this->class, $id);
        };
    }



    /**
     * Maps an instance of this controller to a model
     *
     * @param $class string Model unqualified classname
     *
     * @return Patchwork\Controller\AbstractController Mapped instance
     */
    public static function getInstanceFor($class)
    {
        $instance = new static();
        $instance->class = $class;

        return $instance;
    }



    /**
     * Silex method that binds the controller to the app
     *
     * @param $app Silex\Application Application instance
     *
     * @return Silex\ControllerCollection Object encapsulating crafted routes
     */
    public function connect(Application $app)
    {
        return $this->route($app);
    }



    /**
     * Crafts routes for this instance
     *
     * @param $app   Silex\Application Application instance
     * @param $class string            Model unqualified classname
     *
     * @return Silex\ControllerCollection Object encapsulating crafted routes
     */
    protected function route(Application $app)
    {
        return $app['controllers_factory'];
    }
}
