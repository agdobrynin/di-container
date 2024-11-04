<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\DiFactory;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Unit\Attribute\DiFactory\Fixtures\ClassA;
use Tests\Unit\Attribute\DiFactory\Fixtures\ClassAWithPropertyByFactoryFail;
use Tests\Unit\Attribute\DiFactory\Fixtures\ClassAWithPropertyByFactorySuccess;
use Tests\Unit\Attribute\DiFactory\Fixtures\ClassManyAttributeOnClass;
use Tests\Unit\Attribute\DiFactory\Fixtures\PropertyVariadicSuccessTest;
use Tests\Unit\Attribute\DiFactory\Fixtures\RuleA;
use Tests\Unit\Attribute\DiFactory\Fixtures\RuleB;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionSimple
 *
 * @internal
 */
class DiFactoryTest extends TestCase
{
    public function testMakeFromReflectionForClassSuccess(): void
    {
        $container = (new DiContainerFactory())->make();
        $class = $container->get(ClassA::class);

        $this->assertEquals('make from Tests\Unit\Attribute\DiFactory\Fixtures\ClassADiFactory', $class->dependency->name);
    }

    public function testMakeFromReflectionForClassFailByCountAttribute(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('can only be applied once per class');

        $container->get(ClassManyAttributeOnClass::class);
    }

    public function testMakeFromReflectionForPropertyNonVariadicSuccess(): void
    {
        $container = (new DiContainerFactory())->make();
        $class = $container->get(ClassAWithPropertyByFactorySuccess::class);

        $this->assertEquals('make from Tests\Unit\Attribute\DiFactory\Fixtures\ClassDependencyOnPropertyDiFactory', $class->dependency->name);
    }

    public function testMakeFromReflectionForPropertyNonVariadicFail(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('can only be applied once per non-variadic parameter');

        $container->get(ClassAWithPropertyByFactoryFail::class);
    }

    public function testMakeFromReflectionForPropertyVariadicSuccess(): void
    {
        $container = (new DiContainerFactory())->make();
        $class = $container->get(PropertyVariadicSuccessTest::class);
        $rules = $class->getRules();

        $this->assertCount(2, $rules);
        $this->assertInstanceOf(RuleB::class, \current($rules));
        $this->assertInstanceOf(RuleA::class, \next($rules));
    }
}
