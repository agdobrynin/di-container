<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Attributes\TowClassesWithInjectByReferenceA;
use Tests\Fixtures\Attributes\TowClassesWithInjectByReferenceB;
use Tests\Fixtures\Classes\DependenciesByReference;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\getParameterReflectionType
 *
 * @internal
 */
class ContainerDependenciesByReferenceTest extends TestCase
{
    public function testContainerDependenciesByReference(): void
    {
        $container = (new DiContainerFactory())->make([
            'iterator1' => [
                \ArrayIterator::class,
                DiContainerInterface::ARGUMENTS => [
                    'array' => ['one', 'two', 'three'],
                ],
            ],
            'iterator2' => [
                \ArrayIterator::class,
                DiContainerInterface::ARGUMENTS => [
                    'array' => ['four', 'five', 'six'],
                ],
            ],
            DependenciesByReference::class => [
                DiContainerInterface::ARGUMENTS => [
                    'dependencies1' => '@iterator1',
                    'dependencies2' => '@iterator2',
                ],
            ],
        ]);

        $class = $container->get(DependenciesByReference::class);

        $this->assertEquals(['one', 'two', 'three'], $class->dependencies1->getArrayCopy());
        $this->assertEquals(['four', 'five', 'six'], $class->dependencies2->getArrayCopy());
        $this->assertNotSame($class->dependencies1, $class->dependencies2);
    }

    public function testContainerDependenciesByReferenceByAttribute(): void
    {
        $container = (new DiContainerFactory())->make([
            'inject1' => [
                \ArrayIterator::class,
                DiContainerInterface::ARGUMENTS => ['array' => ['one', 'two']],
            ],
            'inject2' => [
                \ArrayIterator::class,
                DiContainerInterface::ARGUMENTS => ['array' => ['three', 'four']],
            ],
        ]);

        $this->assertEquals(['one', 'two'], $container->get(TowClassesWithInjectByReferenceA::class)->iterator->getArrayCopy());
        $this->assertEquals(['three', 'four'], $container->get(TowClassesWithInjectByReferenceB::class)->iterator->getArrayCopy());
    }
}
