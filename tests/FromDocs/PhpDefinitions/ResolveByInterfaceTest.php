<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassFirst;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassInterface;

use function array_merge;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\diGet
 *
 * @internal
 */
class ResolveByInterfaceTest extends TestCase
{
    public function testResolveByClass(): void
    {
        $definition = [
            ClassInterface::class => diAutowire(ClassFirst::class)
                // bind by name
                ->bindArguments(file: '/var/log/app.log'),
        ];

        $container = (new DiContainerFactory())->make($definition);
        $myClass = $container->get(ClassInterface::class);

        $this->assertEquals('/var/log/app.log', $myClass->file);
    }

    public function testResolveByByReference(): void
    {
        $classesDefinitions = [
            diAutowire(ClassFirst::class)
                // bind by index
                ->bindArguments('/var/log/app.log'),
        ];

        // ... many definitions ...

        $interfacesDefinitions = [
            ClassInterface::class => diGet(ClassFirst::class),
        ];

        $container = (new DiContainerFactory())->make(
            array_merge($classesDefinitions, $interfacesDefinitions)
        );

        $myClass = $container->get(ClassInterface::class);

        $this->assertEquals('/var/log/app.log', $myClass->file);
    }
}
