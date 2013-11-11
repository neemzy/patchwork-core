<?php

namespace Patchwork\Controller;

use Silex\ControllerProviderInterface;
use Patchwork\App;

abstract class AbstractController implements ControllerProviderInterface
{
    protected $class;
    protected $auth;



    public function __construct()
    {
        $this->auth = function () {
            $app = App::getInstance();

            $username = $app['request']->server->get('PHP_AUTH_USER', false);
            $password = $app['request']->server->get('PHP_AUTH_PW');

            if ((! $username || ! $password) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_REMOTE_USER'], $matches)) {
                list($username, $password) = explode(':', base64_decode($matches[1]));

                $username = strip_tags($username);
                $password = strip_tags($password);
            }

            if (($username != ADMIN_USER) || ($password != ADMIN_PASS)) {
                $response = new Response();
                $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', 'Administration'));
                $response->setStatusCode(401, 'Please sign in.');

                return $response;
            }
        };
    }



    public static function getInstanceFor($class)
    {
        $instance = new static();
        $instance->class = $class;

        return $instance;
    }



    public function connect(App $app)
    {
        return $this->route($app);
    }



    protected function route($app)
    {
        return $app['controllers_factory'];
    }
}
