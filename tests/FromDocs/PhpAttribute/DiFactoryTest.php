<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\ClassOne;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionFactory
 *
 * @internal
 */
class DiFactoryTest extends TestCase
{
    public function testDiFactory(): void
    {
        $container = (new DiContainerFactory())->make();

        $myClass = $container->get(ClassOne::class);

        $this->assertEquals('Piter', $myClass->name);
        $this->assertEquals(22, $myClass->age);
        $this->assertSame($myClass, $container->get(ClassOne::class));
    }
}
