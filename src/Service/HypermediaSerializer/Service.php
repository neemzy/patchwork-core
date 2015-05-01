<?php

namespace Neemzy\Patchwork\Service\HypermediaSerializer;

use Silex\Application;
use Symfony\Component\Validator\Constraints as Assert;
use Neemzy\Patchwork\Model\Entity;

class Service
{
    /**
     * @var Silex\Application App to bind
     */
    protected $app;



    /**
     * @param Silex\Application $app App to bind
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }



    private function export(Entity $model)
    {
        $data = $model->unbox()->export();
        $table = $model->getTableName();

        // Use URL for id
        $data = ['@id' => $this->app['url_generator']->generate('api.'.$table.'.read', ['model' => $data['id']], true)] + $data;
        unset($data['id']);

        // Use configuration, if any
        if (null !== $config = $this->app['config']['hypermedia'][$table]) {
            $data = $config + $data;
        }

        // Use URLs for files
        $baseUrl = rtrim($this->app['url_generator']->generate('home', [], true), '/').'/';

        foreach ($model->getAsserts() as $field => $asserts) {
            foreach ($asserts as $assert) {
                if ($assert instanceof Assert\File) {
                    $data[$field] = $baseUrl.ltrim($model->getFilePath($field), '/');
                    break;
                }
            }
        }

        return $data;
    }



    /**
     * @param Neemzy\Patchwork\Model\Entity|array $data
     *
     * @return mixed
     */
    public function serialize($data)
    {
        if ($data instanceof Entity) {
            $data = $this->export($data);
        } else if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($value instanceof Entity) {
                    $data[$key] = $this->export($value);
                }
            }

            $data = array_values($data);
        }

        return $data;
    }
}
