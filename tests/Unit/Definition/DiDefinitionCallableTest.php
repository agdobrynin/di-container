<?php

declare(strict_types=1);

namespace Tests\Unit\Definition;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Definition\Fixtures\CallableStaticMethodWithArgument;
use Tests\Unit\Definition\Fixtures\SimpleService;

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
class DiDefinitionCallableTest extends TestCase
{
    public function testCallableStringClassStaticMethod(): void
    {
        $container = (new DiContainerFactory())->make(['service' => SimpleService::class]);

        $callable = new DiDefinitionCallable($container, CallableStaticMethodWithArgument::class.'::makeSomething', false);
        $res = $callable->invoke(false);

        $this->assertEquals('Tests\Unit\Definition\Fixtures\SimpleService:Tests\Unit\Definition\Fixtures\WithoutConstructor:ok', $res);
    }
}
