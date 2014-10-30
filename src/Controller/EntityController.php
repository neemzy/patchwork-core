<?php

namespace Neemzy\Patchwork\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;

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
}
