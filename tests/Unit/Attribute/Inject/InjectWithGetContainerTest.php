<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Inject;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Unit\Attribute\Inject\Fixtures\ClassWithInjectByAttributeTowServicesOneTypeSingletonFalse;
use Tests\Unit\Attribute\Inject\Fixtures\FreeInterfaceByInjectClass;
use Tests\Unit\Attribute\Inject\Fixtures\InjectArgumentByArgumentName;
use Tests\Unit\Attribute\Inject\Fixtures\InjectDependencyWithPrivateConstructor;
use Tests\Unit\Attribute\Inject\Fixtures\InjectMultiNonVariadicConstructorParameter;
use Tests\Unit\Attribute\Inject\Fixtures\InjectVariadicArgumentByArgumentName;
use Tests\Unit\Attribute\Inject\Fixtures\PropertyNonVariadicReferenceInjectId;
use Tests\Unit\Attribute\Inject\Fixtures\PropertyVariadicByIdWithClass;
use Tests\Unit\Attribute\Inject\Fixtures\PropertyVariadicByIdWithClassWithArgument;
use Tests\Unit\Attribute\Inject\Fixtures\PropertyVariadicReferenceInjectId;
use Tests\Unit\Attribute\Inject\Fixtures\PropertyVariadicWithEmptyInjectId;
use Tests\Unit\Attribute\Inject\Fixtures\RuleA;
use Tests\Unit\Attribute\Inject\Fixtures\RuleB;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionSimple
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 *
 * @internal
 */
class InjectWithGetContainerTest extends TestCase
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
        $class = $container->get(PropertyVariadicByIdWithClass::class);

        $rules = $class->getRules();

        $this->assertCount(2, $rules);
        $this->assertInstanceOf(RuleB::class, $rules[0]);
        $this->assertEquals('mail', $rules[0]->rule);
        $this->assertInstanceOf(RuleA::class, $rules[1]);
        $this->assertEquals('trim', $rules[1]->rule);
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

    public function testInjectVariadicByReferenceInjectId(): void
    {
        $container = (new DiContainerFactory())->make([
            'ruleA' => RuleA::class,
            'ruleB' => RuleB::class,
        ]);
        $class = $container->get(PropertyVariadicReferenceInjectId::class);

        $rules = $class->getRules();

        $this->assertCount(2, $rules);
        $this->assertInstanceOf(RuleA::class, $rules[0]);
        $this->assertInstanceOf(RuleB::class, $rules[1]);
    }

    public function testPropertyNonVariadicReferenceInjectId(): void
    {
        $container = (new DiContainerFactory())->make([
            'ruleA' => RuleA::class,
        ]);
        $class = $container->get(PropertyNonVariadicReferenceInjectId::class);

        $this->assertInstanceOf(RuleA::class, $class->rule);
    }

    public function testPropertyVariadicInjectByClassWithArguments(): void
    {
        $container = (new DiContainerFactory())->make();

        $class = $container->get(PropertyVariadicByIdWithClassWithArgument::class);

        $rules = $class->getRules();

        $this->assertCount(2, $rules);
        $this->assertInstanceOf(RuleB::class, $rules[0]);
        $this->assertEquals('address', $rules[0]->rule);
        $this->assertInstanceOf(RuleA::class, $rules[1]);
        $this->assertEquals('zip', $rules[1]->rule);

        $this->assertNotSame(
            $container->get(PropertyVariadicByIdWithClassWithArgument::class)->getRules()[0],
            $container->get(PropertyVariadicByIdWithClassWithArgument::class)->getRules()[0],
        );

        $this->assertSame(
            $container->get(PropertyVariadicByIdWithClassWithArgument::class)->getRules()[1],
            $container->get(PropertyVariadicByIdWithClassWithArgument::class)->getRules()[1],
        );
    }

    public function testUnresolvedInterfaceByInject(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Unresolvable dependency');

        (new DiContainerFactory())->make()->get(FreeInterfaceByInjectClass::class);
    }

    public function testResolveNonVariadicParameterWithoutType(): void
    {
        $container = (new DiContainerFactory())->make(['argName' => ['hello', 'world']]);

        $this->assertEquals(['hello', 'world'], $container->get(InjectArgumentByArgumentName::class)->argName);
    }

    public function testResolveVariadicParameterWithoutType(): void
    {
        $container = (new DiContainerFactory())->make(['argName' => [['hello', 'world'], ['run', 'now']]]);
        $class = $container->get(InjectVariadicArgumentByArgumentName::class);

        $this->assertEquals(['hello', 'world'], $class->argNames[0]);
        $this->assertEquals(['run', 'now'], $class->argNames[1]);
        $this->assertEquals([['hello', 'world'], ['run', 'now']], $class->argNames);
    }

    public function testInjectTwoServicesOneType(): void
    {
        $container = (new DiContainerFactory())->make();

        $class = $container->get(ClassWithInjectByAttributeTowServicesOneTypeSingletonFalse::class);

        $this->assertEquals(['one', 'two'], $class->iterator1->getArrayCopy());
        $this->assertEquals(['three', 'four'], $class->iterator2->getArrayCopy());

        $this->assertInstanceOf(\ArrayIterator::class, $class->iterator1);
        $this->assertInstanceOf(\ArrayIterator::class, $class->iterator2);

        $this->assertNotSame($class->iterator1, $class->iterator2);
    }

    public function testResolveConstructorArgumentWithSubClassWithPrivateConstructor(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/(DependencyWithPrivateConstructor).+(class is not instantiable)/');

        $container->get(InjectDependencyWithPrivateConstructor::class);
    }
}
