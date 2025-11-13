<?php

declare(strict_types=1);

namespace Tests\DiContainerCall;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\DiContainerCall\Fixtures\ClassWithSimplePublicProperty;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue::getDefinition
 * @covers \Kaspi\DiContainer\functionName
 * @covers \Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition
 *
 * @internal
 */
class CallClassDefinitionTest extends TestCase
{
    public function testCallWithArgumentsInvokeClassWithoutPhpAttribute(): void
    {
        $config = new DiContainerConfig(useAttribute: false);
        $container = new DiContainer([
            diAutowire(ClassWithSimplePublicProperty::class)
                // bind by name
                ->bindArguments(publicProperty: 'Ready'),
        ], $config);

        $res = $container->call(ClassWithSimplePublicProperty::class, ['append' => '🚀']);

        $this->assertEquals('Ready invoke 🚀', $res);
    }

    public function testCallInvokeClassArgumentDefaultValueWithoutPhpAttribute(): void
    {
        $config = new DiContainerConfig(useAttribute: false);
        $container = new DiContainer([
            diAutowire(ClassWithSimplePublicProperty::class)
                // bind by index
                ->bindArguments('Ready'),
        ], $config);

        $res = $container->call(ClassWithSimplePublicProperty::class);

        $this->assertEquals('Ready', $res);
    }

    public function testCallWithArgumentsClassWithNoneStaticMethodAsStringWithoutPhpAttribute(): void
    {
        $config = new DiContainerConfig(useAttribute: false);
        $container = new DiContainer([
            diAutowire(ClassWithSimplePublicProperty::class)
                ->bindArguments(publicProperty: 'Start'),
        ], $config);

        $res = $container->call(ClassWithSimplePublicProperty::class.'::method', ['append' => '🚩']);

        $this->assertEquals('Start method 🚩', $res);
    }

    public function testCallWithArgumentsFromStaticMethodAsString(): void
    {
        $container = (new DiContainerFactory())->make();
        $res = $container->call(ClassWithSimplePublicProperty::class.'::staticMethod', ['append' => '🗿']);

        $this->assertEquals('static method 🗿', $res);
    }
}
