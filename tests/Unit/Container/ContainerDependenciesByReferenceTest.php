<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Classes\DependenciesByReference;

/**
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerDefinition
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
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

        $this->assertEquals(['one', 'two', 'three'], $container->get(DependenciesByReference::class)->dependencies1->getArrayCopy());
        $this->assertEquals(['four', 'five', 'six'], $container->get(DependenciesByReference::class)->dependencies2->getArrayCopy());
    }
}
