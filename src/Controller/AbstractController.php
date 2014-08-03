<?php

namespace Patchwork\Controller;

use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use \RedBean_Facade as R;
use Patchwork\App;

abstract class AbstractController implements ControllerProviderInterface
{
    protected $class;
    protected $auth;
    protected $beanProvider;



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



    public static function getInstanceFor($class)
    {
        $instance = new static();
        $instance->class = $class;

        return $instance;
    }



    public function connect(\Silex\Application $app)
    {
        return $this->route($app);
    }



    protected function route($app)
    {
        return $app['controllers_factory'];
    }
}
