<?php

declare(strict_types=1);

namespace Tests\Unit\AttributeInject;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Unit\AttributeInject\Fixtures\InjectMultiNonVariadicConstructorParameter;
use Tests\Unit\AttributeInject\Fixtures\PropertyVariadicSuccess;
use Tests\Unit\AttributeInject\Fixtures\PropertyVariadicWithEmptyInjectId;
use Tests\Unit\AttributeInject\Fixtures\RuleA;
use Tests\Unit\AttributeInject\Fixtures\RuleB;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 *
 * @internal
 */
class InjectTest extends TestCase
{
    public function testInjectNonVariadicParameterWithMultiInject(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('can only be applied once per non-variadic parameter');

        $container->get(InjectMultiNonVariadicConstructorParameter::class);
    }

    public function testInjectVariadicParameterByClassIdInInjectAttribute(): void
    {
        $container = (new DiContainerFactory())->make();
        $class = $container->get(PropertyVariadicSuccess::class);

        $rules = $class->getRules();

        $this->assertCount(2, $rules);
        $this->assertInstanceOf(RuleB::class, $rules[0]);
        $this->assertInstanceOf(RuleA::class, $rules[1]);
    }

    public function testInjectVariadicByInterfaceWithEmptyInjectId(): void
    {
        $container = (new DiContainerFactory())->make();
        $class = $container->get(PropertyVariadicWithEmptyInjectId::class);

        $rules = $class->getRules();

        $this->assertCount(2, $rules);
        $this->assertInstanceOf(RuleB::class, $rules[0]);
        $this->assertInstanceOf(RuleB::class, $rules[1]);
    }
}
