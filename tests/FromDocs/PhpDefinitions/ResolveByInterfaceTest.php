<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassFirst;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassInterface;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class ResolveByInterfaceTest extends TestCase
{
    public function testResolveByClass(): void
    {
        $definition = [
            ClassInterface::class => diAutowire(ClassFirst::class)
                ->addArgument('file', '/var/log/app.log'),
        ];

        $container = (new DiContainerFactory())->make($definition);
        $myClass = $container->get(ClassInterface::class);

        $this->assertEquals('/var/log/app.log', $myClass->file);
    }

    public function testResolveByByReference(): void
    {
        $classesDefinitions = [
            diAutowire(ClassFirst::class)
                ->addArgument('file', '/var/log/app.log'),
        ];

        // ... many definitions ...

        $interfacesDefinitions = [
            ClassInterface::class => diReference(ClassFirst::class),
        ];

        $container = (new DiContainerFactory())->make(
            \array_merge($classesDefinitions, $interfacesDefinitions)
        );

        $myClass = $container->get(ClassInterface::class);

        $this->assertEquals('/var/log/app.log', $myClass->file);
    }
}
