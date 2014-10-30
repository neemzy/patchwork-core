<?php

namespace Neemzy\Patchwork\Service\Hydrator;

use Silex\Application;
use Silex\ServiceProviderInterface;

class Provider implements ServiceProviderInterface
{
    /**
     * Registers this service on the given app
     *
     * @param Silex\Application $app Application instance
     *
     * @return void
     */
    public function register(Application $app)
    {
        $app['hydrator'] = $app->share(function () use ($app) {
            return new Service($app);
        });
    }



    /**
     * Bootstraps the application
     *
     * @return void
     */
    public function boot(Application $app)
    {
    }
}
