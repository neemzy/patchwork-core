<?php

namespace Neemzy\Patchwork\Tests\Model;

class SortableModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Checks model position change
     *
     * @return void
     */
    public function testMove()
    {
        $redbean = $this->getMockBuilder('RedBean')
                        ->setMethods(['exec', 'count'])
                        ->getMock();

        $redbean->expects($this->any())
                ->method('exec');

        $redbean->expects($this->any())
                ->method('count')
                ->will($this->returnValue(2));

        $model = $this->getMockBuilder('Neemzy\Patchwork\Tests\TestEntity')
                      ->setMethods(['getTableName'])
                      ->getMock();

        $model->expects($this->any())
              ->method('getTableName');

        $model->app = compact('redbean');
        $model->position = 1;

        $model->move(true);
        $this->assertEquals(1, $model->position);

        $model->move(false);
        $this->assertEquals(2, $model->position);

        $model->move(false);
        $this->assertEquals(2, $model->position);

        $model->move(true);
        $this->assertEquals(1, $model->position);
    }
}
