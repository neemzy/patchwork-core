<?php

namespace Neemzy\Patchwork\Tests\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Neemzy\Patchwork\Tests\TestEntity;

class EntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Checks model assert list retrieval
     *
     * @return void
     */
    public function testGetAsserts()
    {
        $factory = $this->getMockBuilder('Symfony\Component\Validator\Mapping\ClassMetadataFactory')
                        ->setMethods(['getMetadataFor'])
                        ->getMock();

        $factory->expects($this->once())
                ->method('getMetadataFor')
                ->will(
                    $this->returnCallback(
                        function () {
                            $member1 = new \stdClass();
                            $member1->constraints = [new Assert\NotBlank()];

                            $member2 = new \stdClass();
                            $member2->constraints = [new Assert\NotBlank(), new Assert\Image()];

                            $metadata = new \stdClass();

                            $metadata->members = [
                                'field1' => [$member1],
                                'field2' => [$member2]
                            ];

                            return $metadata;
                        }
                    )
                );

        $model = $this->getMockBuilder('Neemzy\Patchwork\Tests\TestEntity')
                      ->setMethods(['getTableName']) // specify method to trigger mocking
                      ->getMock();

        $model->app = ['validator.mapping.class_metadata_factory' => $factory];

        $asserts = $model->getAsserts();
        $this->assertCount(2, $asserts);

        $this->assertArrayHasKey('field1', $asserts);
        $this->assertCount(1, $asserts['field1']);
        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\NotBlank', $asserts['field1'][0]);

        $this->assertArrayHasKey('field2', $asserts);
        $this->assertCount(2, $asserts['field2']);
        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\NotBlank', $asserts['field2'][0]);
        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\Image', $asserts['field2'][1]);
    }



    /**
     * Checks model recursive used trait list retrieval
     *
     * @return void
     */
    public function testGetRecursiveTraits()
    {
        $reflection = new \ReflectionClass('Neemzy\Patchwork\Tests\TestEntity');
        $method = $reflection->getMethod('getRecursiveTraits');
        $method->setAccessible(true);

        $model = new TestEntity();
        $traits = $method->invoke($model);

        $this->assertCount(4, $traits);
        $this->assertContains('Neemzy\Patchwork\Model\FileModel', $traits);
        $this->assertContains('Neemzy\Patchwork\Model\SlugModel', $traits);
        $this->assertContains('Neemzy\Patchwork\Model\SortableModel', $traits);
        $this->assertContains('Neemzy\Patchwork\Model\TogglableModel', $traits);
    }
}
