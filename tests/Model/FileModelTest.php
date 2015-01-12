<?php

namespace Neemzy\Patchwork\Tests\Model;

use Neemzy\Patchwork\Tests\TestEntity;

class FileModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Checks file path retrieval
     *
     * @return void
     */
    public function testGetFilePath()
    {
        $model = $this->getMockBuilder('Neemzy\Patchwork\Tests\TestEntity')
                      ->setMethods(['getUploadPath'])
                      ->getMock();

        $model->expects($this->any())
              ->method('getUploadPath')
              ->will(
                  $this->returnValueMap(
                      [
                          [true, '/absolute/path/to/files/'],
                          [false, 'path/to/files/']
                      ]
                  )
              );

        $model->file = 'filename.jpg';
        $this->assertEquals('/absolute/path/to/files/filename.jpg', $model->getFilePath('file', true));
        $this->assertEquals('path/to/files/filename.jpg', $model->getFilePath('file'));
    }



    /**
     * Checks file name generation
     *
     * @return void
     */
    public function testGenerateFilename()
    {
        $reflection = new \ReflectionClass('Neemzy\Patchwork\Tests\TestEntity');
        $method = $reflection->getMethod('generateFilename');
        $method->setAccessible(true);

        $model = new TestEntity();

        $this->assertRegExp('/[a-z0-9]+\.jpg/', $method->invokeArgs($model, [__DIR__, 'jpg']));
    }
}
