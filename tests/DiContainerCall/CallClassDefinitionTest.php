<?php

declare(strict_types=1);

namespace Tests\DiContainerCall;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\ClassWithSimplePublicProperty;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 *
 * @internal
 */
class CallClassDefinitionTest extends TestCase
{
    public function testClassWithInvokeMethod(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(ClassWithSimplePublicProperty::class)
                ->addArgument('publicProperty', 'Ready'),
        ]);

        $res = $container->call(ClassWithSimplePublicProperty::class, ['append' => '🚀']);

        $this->assertEquals('Ready invoke 🚀', $res);
    }

    /**
     * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
     */
    public function testClassWithInvokeMethodArgumentDefault(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(ClassWithSimplePublicProperty::class)
                ->addArgument('publicProperty', 'Ready'),
        ]);

        $res = $container->call(ClassWithSimplePublicProperty::class);

        $this->assertEquals('Ready', $res);
    }

    public function testClassWithNoneStaticMethodAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(ClassWithSimplePublicProperty::class)
                ->addArgument('publicProperty', 'Start'),
        ]);

        $res = $container->call(ClassWithSimplePublicProperty::class.'::method', ['append' => '🚩']);

        $this->assertEquals('Start method 🚩', $res);
    }

    /**
     * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue::getDefinition
     * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
     */
    public function testClassWithNoneStaticMethodAsArray(): void
    {
        $container = new DiContainer([
            // global definition when resolve class container will get dependency by argument name.
            'publicProperty' => 'Try call as array from',
            diAutowire(ClassWithSimplePublicProperty::class),
        ]);

        $res = $container->call([ClassWithSimplePublicProperty::class, 'method'], ['append' => '🌞']);

        $this->assertEquals('Try call as array from method 🌞', $res);
    }

    public function testCallFromStaticMethod(): void
    {
        $container = (new DiContainerFactory())->make();
        $res = $container->call(ClassWithSimplePublicProperty::class.'::staticMethod', ['append' => '🗿']);

        $this->assertEquals('static method 🗿', $res);
    }
}
