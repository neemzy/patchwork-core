<?php

namespace Neemzy\Patchwork\Tests\Model;

use Neemzy\Patchwork\Tests\TestEntity;

class TogglableModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Checks model state toggling
     *
     * @return void
     */
    public function testToggle()
    {
        $model = new TestEntity();
        $model->active = false;

        $model->toggle();
        $this->assertTrue($model->active);

        $model->toggle(true);
        $this->assertTrue($model->active);

        $model->toggle();
        $model->toggle(false);
        $this->assertFalse($model->active);
    }
}
