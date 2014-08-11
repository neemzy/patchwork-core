<?php

namespace Patchwork\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class FrontController extends AbstractController
{
    /**
     * Crafts routes for this instance
     *
     * @param Silex\Application $app Application instance
     *
     * @return Silex\ControllerCollection Object encapsulating crafted routes
     */
    protected function route(Application $app)
    {
        $ctrl = parent::route($app);



        /**
         * Homepage
         */
        $ctrl
            ->get(
                '/',
                function () use ($app) {
                    $root = str_replace('index.php/', '', $app['url_generator']->generate('home'));

                    if ($app['request']->getRequestURI() != $root) {
                        return $app->redirect($root, Response::HTTP_MOVED_PERMANENTLY);
                    }

                    return $app['twig']->render('front/partials/home.twig');
                }
            )
            ->bind('home');



        /**
         * Admin root
         */
        $ctrl
            ->get(
                '/admin',
                function () use ($app) {
                    return $app->redirect($app['url_generator']->generate(ADMIN_ROOT));
                }
            );


        /**
         * robots.txt
         */
        $ctrl
            ->get(
                '/robots.txt',
                function () use ($app) {
                    $response = new Response('User-agent: *'.PHP_EOL.($app['debug'] ? 'Disallow: /' : 'Sitemap: '.$app['url_generator']->generate('home').'sitemap.xml'));
                    $response->headers->set('Content-Type', 'text/plain');
                    return $response;
                }
            );

        
        
        return $ctrl;
    }
}
