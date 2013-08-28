<?php

namespace Patchwork\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class FrontController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        return $this->route($app);
    }



    protected function route($app)
    {
        $ctrl = $app['controllers_factory'];



        // robots.txt

        $ctrl->get(
            '/robots.txt',
            function () use ($app) {
                $response = new Response('User-agent: *'.PHP_EOL.($app['debug'] ? 'Disallow: /' : 'Sitemap: '.$app['url_generator']->generate('home').'sitemap.xml'));
                $response->headers->set('Content-Type', 'text/plain');
                return $response;
            }
        );



        // LESS

        $ctrl->get(
            '/assets/css/{file}.less',
            function ($file) use ($app) {
                $lessc = new \lessc();
                $dir = BASE_PATH.'/public/assets/css/';
                $less = $dir.$file.'.less';
                $css = $dir.$file.'.css';

                if (! file_exists($less)) {
                    $app->abort(404);
                }

                if (! $app['debug']) {
                    $lessc->setFormatter('compressed');
                    $lessc->checkedCompile($less, $css);
                    $response = new Response(file_get_contents($css));
                } else {
                    $response = new Response($lessc->compileFile($less));
                }
                
                $response->headers->set('Content-Type', 'text/css');
                return $response;
            }
        );



        // Authorized vendor assets

        $ctrl->get(
            '/vendor/{vendor}/{filename}',
            function ($vendor, $filename) use ($app) {
                $filename = BASE_PATH.'/vendor/'.$vendor.'/'.$filename;

                try {
                    $file = new File($filename, true);
                    return new Response(file_get_contents($filename), 200, array('Content-Type' => ($file->getExtension() == 'js' ? 'application/javascript' : $file->getMimeType())));
                } catch (FileNotFoundException $e) {
                    $app->abort(404);
                }
            }
        )->assert('vendor', '(neemzy/patchwork-core/assets|twitter/bootstrap)')->assert('filename', '.+');



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

                if ($_SERVER['REQUEST_URI'] != $root) {
                    return $app->redirect($root, 301);
                }

                return $app['twig']->render('front/home.twig');
            }
        )->bind('home');

        
        
        return $ctrl;
    }
}
