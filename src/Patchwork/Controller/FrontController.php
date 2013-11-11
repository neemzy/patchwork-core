<?php

namespace Patchwork\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use JsMin\Minify as JSMin;
use Patchwork\Helper\Tools;

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



        // LESS

        $ctrl->get(
            '/assets/css/{filename}.less',
            function ($filename) use ($app) {
                $lessc = new \lessc();
                $dir = BASE_PATH.'/public/assets/css/';
                $less = $dir.$filename.'.less';
                $css = $dir.$filename.'.css';

                if (! file_exists($less)) {
                    $app->abort(404);
                }

                if (! $app['debug']) {
                    $lessc->setFormatter('compressed');
                    $lessc->checkedCompile($less, $css);
                    $response = Tools::staticResponse($css);
                } else {
                    $response = new Response($lessc->compileFile($less));
                }
                
                $response->headers->set('Content-Type', 'text/css');
                return $response;
            }
        )->assert('filename', '.+');



        // JSMin

        $ctrl->get(
            '/{path}/{filename}.js',
            function ($path, $filename) use ($app) {
                $filename = BASE_PATH.'/'.((strpos($path, 'assets') === 0) ? 'public/' : '').$path.'/'.$filename.'.js';

                if (! file_exists($filename)) {
                    $app->abort(404);
                }

                $js = file_get_contents($filename);

                if (! $app['debug']) {
                    $response = Tools::staticResponse($filename, JSMin::minify($js));
                } else {
                    $response = new Response($js);
                }
                
                $response->headers->set('Content-Type', 'application/javascript');
                return $response;
            }
        )->assert('path', '(vendor|assets/js)')->assert('filename', '.+');



        // Authorized vendor assets

        $ctrl->get(
            '/vendor/{vendor}/{filename}',
            function ($vendor, $filename) use ($app) {
                $filename = BASE_PATH.'/vendor/'.$vendor.'/'.$filename;

                try {
                    $file = new File($filename, true);
                    $response = Tools::staticResponse($filename);
                    $response->headers->set('Content-Type', $file->getMimeType());
                    return $response;
                } catch (FileNotFoundException $e) {
                    $app->abort(404);
                }
            }
        )->assert('vendor', '(neemzy/patchwork-core/assets|twbs/bootstrap)')->assert('filename', '.+');



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
