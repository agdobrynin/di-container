<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Attributes\TowClassesWithInjectByReferenceA;
use Tests\Fixtures\Attributes\TowClassesWithInjectByReferenceB;
use Tests\Fixtures\Classes\DependenciesByReference;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\InjectByReference
 * @covers \Kaspi\DiContainer\Attributes\InjectContext
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 *
 * @internal
 */
class ContainerDependenciesByReferenceTest extends TestCase
{
    public function testContainerDependenciesByReference(): void
    {
        $container = (new DiContainerFactory())->make([
            'iterator1' => diAutowire(\ArrayIterator::class)
                ->addArgument('array', ['one', 'two', 'three']),
            'iterator2' => diAutowire(\ArrayIterator::class)
                ->addArgument('array', ['four', 'five', 'six']),
            diAutowire(DependenciesByReference::class)
                ->addArgument('dependencies1', diReference('iterator1'))
                ->addArgument('dependencies2', diReference('iterator2')),
        ]);

        $class = $container->get(DependenciesByReference::class);

        $this->assertEquals(['one', 'two', 'three'], $class->dependencies1->getArrayCopy());
        $this->assertEquals(['four', 'five', 'six'], $class->dependencies2->getArrayCopy());
        $this->assertNotSame($class->dependencies1, $class->dependencies2);
    }

    public function testContainerDependenciesByReferenceByAttribute(): void
    {
        $container = (new DiContainerFactory())->make([
            'inject1' => diAutowire(\ArrayIterator::class, ['array' => ['one', 'two']]),
            'inject2' => diAutowire(\ArrayIterator::class, ['array' => ['three', 'four']]),
        ]);

        $this->assertEquals(['one', 'two'], $container->get(TowClassesWithInjectByReferenceA::class)->iterator->getArrayCopy());
        $this->assertEquals(['three', 'four'], $container->get(TowClassesWithInjectByReferenceB::class)->iterator->getArrayCopy());
    }
}
