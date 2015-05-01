<?php

namespace Neemzy\Patchwork\Tests\Service\HypermediaSerializer;

use Silex\Application;
use Neemzy\Patchwork\Service\HypermediaSerializer\Service;

class ServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testHydrate()
    {
        $model = $this
            ->getMockBuilder('Neemzy\Patchwork\Tests\TestEntity')
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

        $model->id = 8;
        $model->field1 = 'Value 1';
        $model->field2 = 'Value 2';
        $model->field3 = 'Value 3';
        $model->field4 = 'feelsgoodman.jpg';

        $urlGenerator = $this
            ->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $urlGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->will(
                $this->returnValueMap(
                    [
                        ['api.test.read', ['model' => $model->id], true, 'http://example.com/api/test/8'],
                        ['home', [], true, 'http://example.com/']
                    ]
                )
            );

        $app = new Application();

        $service = new Service($app);
        $data = $service->serialize($model);

        $this->assertInternalType('array', $data);
        $this->assertCount(5, $data);
        $this->assertArrayHasKey('@id', $data);
        $this->assertEquals('http://example.com/api/test/8', $data['@id']);
        $this->assertArrayHasKey('field1', $data);
        $this->assertEquals('Value 1', $data['field1']);
        $this->assertArrayHasKey('field2', $data);
        $this->assertEquals('Value 2', $data['field2']);
        $this->assertArrayHasKey('field3', $data);
        $this->assertEquals('Value 3', $data['field3']);
        $this->assertArrayHasKey('field4', $data);
        $this->assertEquals('http://example.com/uploads/test/feelsgoodman.jpg', $data['field4']);
    }
}
