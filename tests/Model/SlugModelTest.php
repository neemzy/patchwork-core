<?php

namespace Neemzy\Patchwork\Tests\Model;

class SlugModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Checks model slug generation
     *
     * @return void
     */
    public function testSlugify()
    {
        $model = $this->getMock('Neemzy\Patchwork\Tests\TestEntity', ['__toString', 'getTableName']);
        $model->expects($this->once())->method('getTableName')->will($this->returnValue('test'));

        $model->expects($this->any())->method('__toString')->will(
            $this->onConsecutiveCalls(' Sample  string -representation-', '!$#@&%£?')
        );

        $model->id = 12;

        $this->assertEquals('sample-string-representation', $model->slugify());
        $this->assertEquals('test-12', $model->slugify());
    }
}
