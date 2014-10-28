<?php

namespace Neemzy\Patchwork\Tests\Controller;

use Neemzy\Patchwork\Tests\TestController;

class EntityControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Checks model hydration with scalar values
     *
     * @return void
     */
    public function testHydrate()
    {
        $model = $this->getMock('Neemzy\Patchwork\Tests\TestEntity', ['getAsserts']);

        $model->expects($this->once())->method('getAsserts')->will(
            $this->returnValue(
                [
                    'field1' => null,
                    'field2' => null,
                    'field3' => null,
                    'field4' => null
                ]
            )
        );

        $filebag = $this->getMock('FileBag', ['has']);
        $filebag->expects($this->any())->method('has')->will($this->returnValue(false));

        $request = $this->getMock('Symfony\Component\HttpFoundation\Request', ['get']);
        $request->files = $filebag;

        $request->expects($this->any())->method('get')->will(
            $this->returnValueMap(
                [
                    ['field1', null, false, 'Value 1'],
                    ['field2', null, false, 'Value 2'],
                    ['field3', null, false, " trim me\n"],
                    ['field4', null, false, '<br />']
                ]
            )
        );

        $reflection = new \ReflectionClass('Neemzy\Patchwork\Tests\TestController');
        $method = $reflection->getMethod('hydrate');
        $method->setAccessible(true);

        $controller = new TestController();
        $method->invokeArgs($controller, [&$model, $request]);

        $this->assertEquals('Value 1', $model->field1);
        $this->assertEquals('Value 2', $model->field2);
        $this->assertEquals('trim me', $model->field3);
        $this->assertEquals('', $model->field4);
    }
}
