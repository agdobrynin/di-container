<?php

declare(strict_types=1);

namespace Tests\Unit\Definition;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Definition\Fixtures\CallableStaticMethodWithArgument;
use Tests\Unit\Definition\Fixtures\ClassWithInvokeMethod;
use Tests\Unit\Definition\Fixtures\SimpleService;
use Tests\Unit\Definition\Fixtures\WithoutConstructor;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 *
 * @internal
 */
class DiDefinitionCallableTest extends TestCase
{
    public function testCallableStringClassStaticMethod(): void
    {
        $container = (new DiContainerFactory())->make(['service' => diAutowire(SimpleService::class)]);

        $callable = new DiDefinitionCallable(CallableStaticMethodWithArgument::class.'::makeSomething', false);
        $res = $callable->setContainer($container)->invoke();

        $this->assertEquals('Tests\Unit\Definition\Fixtures\SimpleService:Tests\Unit\Definition\Fixtures\WithoutConstructor:ok', $res);
    }

    public function testCallableClassWithInvokeMethid(): void
    {
        $container = (new DiContainerFactory())->make();

        $res = (new DiDefinitionCallable(ClassWithInvokeMethod::class))->setContainer($container)->invoke();

        $this->assertInstanceOf(WithoutConstructor::class, $res[0]);
        $this->assertInstanceOf(SimpleService::class, $res[1]);
    }
}
