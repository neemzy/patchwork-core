<?php

namespace Patchwork;

use Silex\Application;
use Silex\ConstraintValidatorFactory;
use Silex\Provider\ValidatorServiceProvider as BaseValidatorServiceProvider;
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\DefaultTranslator;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;

class ValidatorServiceProvider extends BaseValidatorServiceProvider
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
        $app['validator'] = $app->share(function ($app) {
            return new Validator(
                $app['validator.mapping.class_metadata_factory'],
                $app['validator.validator_factory'],
                isset($app['translator']) ? $app['translator'] : new DefaultTranslator(),
                'validators',
                $app['validator.object_initializers']
            );
        });

        $app['validator.mapping.class_metadata_factory'] = $app->share(function ($app) {
            return new ClassMetadataFactory(new StaticMethodLoader());
        });

        $app['validator.validator_factory'] = $app->share(function () use ($app) {
            $validators = isset($app['validator.validator_service_ids']) ? $app['validator.validator_service_ids'] : [];

            return new ConstraintValidatorFactory($app, $validators);
        });

        $app['validator.object_initializers'] = $app->share(function ($app) {
            return [];
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
