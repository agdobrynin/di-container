<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassFirst;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassInterface;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class ResolveByInterfaceTest extends TestCase
{
    public function testResolveByInterface(): void
    {
        $definition = [
            ClassInterface::class => diAutowire(ClassFirst::class)
                ->addArgument('file', '/var/log/app.log'),
        ];

        $container = (new DiContainerFactory())->make($definition);
        $myClass = $container->get(ClassInterface::class);

        $this->assertEquals('/var/log/app.log', $myClass->file);
    }
}
