<?php

namespace Patchwork\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Patchwork\App;

abstract class AbstractController implements ControllerProviderInterface
{
    /**
     * @var string Unqualified model name
     */
    protected $class;

    /**
     * @var closure Authentication callback
     */
    protected $auth;

    /**
     * @var closure Bean provider
     */
    protected $beanProvider;



    /**
     * Constructor
     * Attaches a model provider and an authentication method to the controller
     *
     * @return void
     */
    public function __construct()
    {
        $app = App::getInstance();

        $this->auth = function () use ($app) {
            if (!$app['debug']) {
                $username = $app['request']->server->get('PHP_AUTH_USER', false);
                $password = $app['request']->server->get('PHP_AUTH_PW');

                if ((!$username || !$password) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_REMOTE_USER'], $matches)) {
                    list($username, $password) = explode(':', base64_decode($matches[1]));

                    $username = strip_tags($username);
                    $password = strip_tags($password);
                }

                if (($username != $app['config']['admin_user']) || ($password != $app['config']['admin_pass'])) {
                    $response = new Response(null, Response::HTTP_UNAUTHORIZED);
                    $response->headers->set('WWW-Authenticate', 'Basic realm="Administration"');

                    return $response;
                }
            }
        };

        $this->beanProvider = function ($id) use ($app) {
            return $app['redbean']->load($this->class, $id);
        };
    }



    /**
     * Maps an instance of this controller to a model
     *
     * @param string $class Model unqualified classname
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
     * @param Silex\Application $app Application instance
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
     * @param Silex\Application $app Application instance
     *
     * @return Silex\ControllerCollection Object encapsulating crafted routes
     */
    protected function route(Application $app)
    {
        return $app['controllers_factory'];
    }
}
