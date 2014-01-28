<?php

namespace Patchwork\Controller;

use Symfony\Component\HttpFoundation\Response;

class FrontController extends AbstractController
{
    protected function route($app)
    {
        $ctrl = parent::route($app);



        // robots.txt

        $ctrl->get(
            '/robots.txt',
            function () use ($app) {
                $response = new Response('User-agent: *'.PHP_EOL.($app['debug'] ? 'Disallow: /' : 'Sitemap: '.$app['url_generator']->generate('home').'sitemap.xml'));
                $response->headers->set('Content-Type', 'text/plain');
                return $response;
            }
        );



        // Admin root

        $ctrl->get(
            '/admin',
            function () use ($app) {
                return $app->redirect($app['url_generator']->generate(ADMIN_ROOT));
            }
        );



        // Homepage

        $ctrl->get(
            '/',
            function () use ($app) {
                $root = str_replace('index.php/', '', $app['url_generator']->generate('home'));

                if ($app['request']->getRequestURI() != $root) {
                    return $app->redirect($root, 301);
                }

                return $app['twig']->render('front/home.twig');
            }
        )->bind('home');

        
        
        return $ctrl;
    }
}
