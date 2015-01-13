<?php

namespace Neemzy\Patchwork\Tests\Service\Hydrator;

use Silex\Application;
use Neemzy\Patchwork\Service\Hydrator\Service;

class ServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Checks model hydration with scalar values
     *
     * @return void
     */
    public function testHydrate()
    {
        $model = $this->getMockBuilder('Neemzy\Patchwork\Tests\TestEntity')
                      ->setMethods(['getAsserts'])
                      ->getMock();

        $model->expects($this->once())
              ->method('getAsserts')
              ->will(
                  $this->returnValue(
                      [
                          'field1' => null,
                          'field2' => null,
                          'field3' => null,
                          'field4' => null
                      ]
                  )
              );

        $filebag = $this->getMockBuilder('Symfony\Component\HttpFoundation\FileBag')
                        ->setMethods(['has'])
                        ->getMock();

        $filebag->expects($this->any())
                ->method('has')
                ->will($this->returnValue(false));

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
                        ->setMethods(['get'])
                        ->getMock();

        $request->files = $filebag;

        $request->expects($this->any())
                ->method('get')
                ->will(
                    $this->returnValueMap(
                        [
                            ['field1', null, false, 'Value 1'],
                            ['field2', null, false, 'Value 2'],
                            ['field3', null, false, " trim me\n"],
                            ['field4', null, false, '<br />']
                        ]
                    )
                );

        $app = new Application();
        $app['request'] = $request;

        $service = new Service($app);
        $service->hydrate($model);

        $this->assertEquals('Value 1', $model->field1);
        $this->assertEquals('Value 2', $model->field2);
        $this->assertEquals('trim me', $model->field3);
        $this->assertEquals('', $model->field4);
    }
}
