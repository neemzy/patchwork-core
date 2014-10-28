<?php

namespace Neemzy\Patchwork\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ValidatorInterface;
use Neemzy\Patchwork\Model\Entity;

abstract class EntityController implements ControllerProviderInterface
{
    /**
     * @var string Table name
     */
    protected $table;

    /**
     * @var closure Model provider
     */
    protected $modelProvider;



    /**
     * Constructor
     *
     * @param string $table Table name
     *
     * @return void
     */
    public function __construct($table = null)
    {
        $this->table = $table;
    }



    /**
     * Silex method that exposes routes to the app
     * Attaches a model provider to the controller
     *
     * @param Silex\Application $app Application instance
     *
     * @return Silex\ControllerCollection Object encapsulating crafted routes
     */
    public function connect(Application $app)
    {
        $this->modelProvider = function ($id) use ($app) {
            return $app['redbean']->load($this->table, $id);
        };

        return $app['controllers_factory'];
    }



    /**
     * Hydrates a model from a request
     *
     * @param Neemzy\Patchwork\Model\Entity            $model   Model to hydrate
     * @param Symfony\Component\HttpFoundation\Request $request Request to grab data from
     *
     * @return void
     */
    protected function hydrate(Entity &$model, Request $request)
    {
        foreach ($model->getAsserts() as $field => $asserts) {
            if ($request->files->has($field)) {
                $file = $request->files->get($field);

                if ($file instanceof UploadedFile) {
                    $model->dispatch('upload', [$field, $file]);
                }
            } else {
                $value = trim($request->get($field));

                // Detect HTML fields without actual content
                if (empty(strip_tags($value))) {
                    $value = '';
                }

                $model->$field = $value;
            }
        }
    }
}
