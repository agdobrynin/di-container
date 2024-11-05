<?php

declare(strict_types=1);

namespace Tests\Unit\CallCircularDependency;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Exception\CallCircularDependency;
use PHPUnit\Framework\TestCase;
use Tests\Unit\CallCircularDependency\Fixtures\CircularClass;
use Tests\Unit\CallCircularDependency\Fixtures\CircularClassByInject;
use Tests\Unit\CallCircularDependency\Fixtures\CircularClassByInterface;
use Tests\Unit\CallCircularDependency\Fixtures\FirstClass;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\diDefinition
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 *
 * @internal
 */
class CallCircularDependencyTest extends TestCase
{
    public function testCallCircularDependencySimpleValue(): void
    {
        $this->expectException(CallCircularDependency::class);

        (new DiContainerFactory())->make(
            [
                'inject1' => '@inject2',
                'inject2' => '@inject3',
                'inject3' => '@inject1',
            ]
        )->get('inject1');
    }

    public function testCircularDependencyCallCircularDependencyInClass(): void
    {
        $this->expectException(CallCircularDependency::class);

        (new DiContainerFactory())->make()->get(FirstClass::class);
    }

    public function testCircularDependencyCallMethodWithSimpleInject(): void
    {
        $this->expectException(CallCircularDependency::class);

        (new DiContainerFactory())->make(
            [
                'inject1' => '@inject2',
                'inject2' => '@inject3',
                'inject3' => '@inject1',
            ]
        )->call(static fn (#[Inject('@inject1')] string $v) => $v);
    }

    public function testCircularDependencyCallMethodWithInjectClass(): void
    {
        $this->expectException(CallCircularDependency::class);

        (new DiContainerFactory())->make()->call(static fn (FirstClass $class) => $class);
    }

    public function testCircularDependencyCallMethodInvokableClass(): void
    {
        $this->expectException(CallCircularDependency::class);

        (new DiContainerFactory())->make()->call(FirstClass::class);
    }

    public function testCircularDependencyClassByInterface(): void
    {
        $this->expectException(CallCircularDependency::class);

        (new DiContainerFactory())->make(
            [CircularClassByInterface::class => FirstClass::class]
        )->get(CircularClass::class);
    }

    public function testCircularDependencyClassByInterfaceWithInject(): void
    {
        $this->expectException(CallCircularDependency::class);

        (new DiContainerFactory())->make()->get(CircularClassByInject::class);
    }
}
