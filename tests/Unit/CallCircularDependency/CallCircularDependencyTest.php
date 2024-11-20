<?php

declare(strict_types=1);

namespace Tests\Unit\CallCircularDependency;

use Kaspi\DiContainer\Attributes\InjectByReference;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Exception\CallCircularDependency;
use PHPUnit\Framework\TestCase;
use Tests\Unit\CallCircularDependency\Fixtures\CircularClass;
use Tests\Unit\CallCircularDependency\Fixtures\CircularClassByInject;
use Tests\Unit\CallCircularDependency\Fixtures\CircularClassByInterface;
use Tests\Unit\CallCircularDependency\Fixtures\FirstClass;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\InjectContext
 * @covers \Kaspi\DiContainer\Attributes\InjectByReference
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
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
                'inject1' => diReference('inject2'),
                'inject2' => diReference('inject3'),
                'inject3' => diReference('inject1'),
            ]
        )->get('inject1');
    }

    public function testCircularDependencyCallCircularDependencyInClass(): void
    {
        $this->expectException(CallCircularDependency::class);
        $this->expectExceptionMessageMatches('/Trying call cyclical dependency.+FirstClass.+SecondClass.+FirstClass/');

        (new DiContainerFactory())->make()->get(FirstClass::class);
    }

    public function testCircularDependencyCallMethodWithSimpleInject(): void
    {
        $this->expectException(CallCircularDependency::class);
        $this->expectExceptionMessage('Call dependencies: inject1 -> inject2 -> inject3 -> inject1');

        (new DiContainerFactory())->make(
            [
                'inject1' => diReference('inject2'),
                'inject2' => diReference('inject3'),
                'inject3' => diReference('inject1'),
            ]
        )->call(static fn (#[InjectByReference('inject1')] string $v) => $v);
    }

    public function testCircularDependencyCallMethodWithInjectClass(): void
    {
        $this->expectException(CallCircularDependency::class);
        $this->expectExceptionMessageMatches('/(Call dependencies).+(FirstClass ->).+(SecondClass ->).+(ThirdClass ->).+(FirstClass)/');
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
            [CircularClassByInterface::class => diAutowire(FirstClass::class)]
        )->get(CircularClass::class);
    }

    public function testCircularDependencyClassByInterfaceWithInject(): void
    {
        $this->expectException(CallCircularDependency::class);

        (new DiContainerFactory())->make()->get(CircularClassByInject::class);
    }
}
