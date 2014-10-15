<?php

namespace Patchwork\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator;
use Patchwork\Model\AbstractModel;

abstract class AbstractController implements ControllerProviderInterface
{
    /**
     * @var string Unqualified model name
     */
    protected $class;

    /**
     * @var closure Bean provider
     */
    protected $beanProvider;



    /**
     * Maps an instance of this controller to a model
     *
     * @param string $class Model unqualified classname
     *
     * @return Patchwork\Controller\AbstractController
     */
    public static function getInstance($class)
    {
        $instance = new static();
        $instance->class = $class;

        return $instance;
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
        $this->beanProvider = function ($id) use ($app) {
            return $app['redbean']->load($this->class, $id);
        };

        return $app['controllers_factory'];
    }



    /**
     * Hydrates a model from a request
     *
     * @param Patchwork\Model\AbstractModel            $model   Model to hydrate
     * @param Symfony\Component\HttpFoundation\Request $request Request to grab data from
     *
     * @return void
     */
    protected function hydrate(AbstractModel &$model, Request $request)
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



    /**
     * Gets validation errors for a bean
     *
     * @param Patchwork\Model\AbstractModel         $bean      Bean to validate
     * @param Symfony\Component\Validator\Validator $validator Validator instance
     *
     * @return array
     */
    protected function validate(AbstractModel $bean, Validator $validator)
    {
        $errors = [];

        foreach ($validator->validate($bean) as $error) {
            $errors[] = [
                //$app['translator']->trans($error->getPropertyPath()) => $error->getMessage()
                // translation should be done elsewhere (in the template, with a function/filter instead of a tag ?)
                $error->getPropertyPath() => $error->getMessage()
            ];
        }

        return $errors;
    }
}
