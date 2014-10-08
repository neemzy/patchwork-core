<?php

namespace Patchwork\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Image;
use PHPImageWorkshop\Exception\ImageWorkshopException;
use Patchwork\App;
use Patchwork\Tools;
use Patchwork\Model\AbstractModel;

abstract class AbstractController implements ControllerProviderInterface
{
    /**
     * @var string Unqualified model name
     */
    protected $class;

    /**
     * @var closure Authentication callback
     */
    protected $auth;

    /**
     * @var closure Bean provider
     */
    protected $beanProvider;



    /**
     * Constructor
     * Attaches a model provider and an authentication method to the controller
     *
     * @return void
     */
    public function __construct()
    {
        $app = App::getInstance();

        $this->auth = function () use ($app) {
            if (!$app['debug']) {
                $username = $app['request']->server->get('PHP_AUTH_USER', false);
                $password = $app['request']->server->get('PHP_AUTH_PW');

                if ((!$username || !$password) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_REMOTE_USER'], $matches)) {
                    list($username, $password) = explode(':', base64_decode($matches[1]));

                    $username = strip_tags($username);
                    $password = strip_tags($password);
                }

                if (($username != $app['config']['admin_user']) || ($password != $app['config']['admin_pass'])) {
                    $response = new Response(null, Response::HTTP_UNAUTHORIZED);
                    $response->headers->set('WWW-Authenticate', 'Basic realm="Administration"');

                    return $response;
                }
            }
        };

        $this->beanProvider = function ($id) use ($app) {
            return $app['redbean']->load($this->class, $id)->box();
        };
    }



    /**
     * Hydrates a bean from current request
     *
     * @param Patchwork\Model\AbstractModel $bean Bean to hydrate
     *
     * @return void
     */
    protected static function hydrate(AbstractModel &$bean)
    {
        $app = App::getInstance();

        foreach ($bean->getAsserts() as $field => $asserts) {
            if ($app['request']->files->has($field)) {
                $file = $app['request']->files->get($field);

                if ($file instanceof UploadedFile) {
                    // Keep current file path to be able to delete it
                    $tempField = '_'.$field;
                    $bean->$tempField = $bean->$field;
                    $bean->$field = $file;

                    if (UPLOAD_ERR_OK == $file->getError()) {
                        foreach ($asserts as $assert) {
                            // Detect image size constraints and resize accordingly
                            if ($assert instanceof Image) {
                                $path = $file->getPathname();
                                $pathWithExtension = $path.'.'.$file->guessExtension();
                                rename($path, $pathWithExtension);

                                // ImageWorkshop relies on the file's extension for encoding
                                try {
                                    Tools::resize($pathWithExtension, $assert->maxWidth, $assert->maxHeight);
                                } catch (ImageWorkshopException $e) {
                                }

                                rename($pathWithExtension, $path);
                            }
                        }
                    }
                }
            } else {
                $value = trim($app['request']->get($field));

                // Detect HTML fields without actual content
                if (empty(strip_tags($value))) {
                    $value = '';
                }

                $bean->$field = $value;
            }
        }
    }



    /**
     * Gets validation errors for a bean
     *
     * @param Patchwork\Model\AbstractModel $bean Bean to validate
     *
     * @return array
     */
    protected static function validate(AbstractModel $bean)
    {
        $app = App::getInstance();
        $errors = [];

        foreach ($app['validator']->validate($bean) as $error) {
            $errors[] = [
                $app['translator']->trans($error->getPropertyPath()) => $error->getMessage()
            ];
        }

        return $errors;
    }



    /**
     * Maps an instance of this controller to a model
     *
     * @param string $class Model unqualified classname
     *
     * @return Patchwork\Controller\AbstractController
     */
    public static function getInstanceFor($class)
    {
        $instance = new static();
        $instance->class = $class;

        return $instance;
    }



    /**
     * Silex method that binds the controller to the app
     *
     * @param Silex\Application $app Application instance
     *
     * @return Silex\ControllerCollection Object encapsulating crafted routes
     */
    public function connect(Application $app)
    {
        return $this->route($app);
    }



    /**
     * Crafts routes for this instance
     *
     * @param Silex\Application $app Application instance
     *
     * @return Silex\ControllerCollection Object encapsulating crafted routes
     */
    protected function route(Application $app)
    {
        return $app['controllers_factory'];
    }
}
