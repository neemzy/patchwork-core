<?php

namespace Neemzy\Patchwork\Service\Hydrator;

use Silex\Application;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Neemzy\Patchwork\Model\Entity;

class Service
{
    /**
     * @var Silex\Application App to bind
     */
    protected $app;



    /**
     * Constructor
     * Sets up database connection
     *
     * @param Silex\Application $app App to bind
     *
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }



    /**
     * Hydrates a model from a request
     *
     * @param Neemzy\Patchwork\Model\Entity $model Model to hydrate
     *
     * @return void
     */
    public function hydrate(Entity &$model)
    {
        foreach ($model->getAsserts() as $field => $asserts) {
            if ($this->app['request']->files->has($field)) {
                $file = $this->app['request']->files->get($field);

                if ($file instanceof UploadedFile) {
                    $model->dispatch('upload', [$field, $file]);
                }
            } else {
                $value = trim($this->app['request']->get($field));

                // Detect HTML fields without actual content
                if (empty(strip_tags($value))) {
                    $value = '';
                }

                $model->$field = $value;
            }
        }
    }
}
