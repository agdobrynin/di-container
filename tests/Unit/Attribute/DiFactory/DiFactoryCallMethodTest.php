<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\DiFactory;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Unit\Attribute\DiFactory\Fixtures\ClassWithMethodPropertyVariadicSuccess;
use Tests\Unit\Attribute\DiFactory\Fixtures\ClassWithMethodWithParameterNonVariadicByDiFactory;
use Tests\Unit\Attribute\DiFactory\Fixtures\RuleA;
use Tests\Unit\Attribute\DiFactory\Fixtures\RuleB;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 *
 * @internal
 */
class DiFactoryCallMethodTest extends TestCase
{
    public function testCallMethodWithDiFactoryNonVariadic(): void
    {
        $container = (new DiContainerFactory())->make();

        $res = $container->call([ClassWithMethodWithParameterNonVariadicByDiFactory::class, 'myMethod']);

        $this->assertEquals('make from Tests\Unit\Attribute\DiFactory\Fixtures\ClassDependencyOnPropertyDiFactory', $res);
    }

    public function testCallMethodWithDiFactoryNonVariadicWithManyDiFactory(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('can only be applied once per non-variadic parameter');

        $container->call([ClassWithMethodWithParameterNonVariadicByDiFactory::class, 'myMethodFail']);
    }

    public function testCallMethodWithDiFactoryVariadicWithManyDiFactory(): void
    {
        $container = (new DiContainerFactory())->make();

        $rules = $container->call([ClassWithMethodPropertyVariadicSuccess::class, 'getRules']);

        $this->assertCount(2, $rules);
        $this->assertInstanceOf(RuleB::class, \current($rules));
        $this->assertInstanceOf(RuleA::class, \next($rules));
    }
}
