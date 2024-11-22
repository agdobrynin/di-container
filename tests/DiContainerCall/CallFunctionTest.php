<?php

declare(strict_types=1);

namespace Tests\DiContainerCall;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\ClassWithSimplePublicProperty;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 *
 * @internal
 */
class CallFunctionTest extends TestCase
{
    public function testBuiltinFunction(): void
    {
        $container = new DiContainer();
        $res = \round($container->call('log', ['num' => 10]));

        $this->assertEquals(2.0, $res);
    }

    public function testUserFunction(): void
    {
        $definitions = [
            diAutowire(ClassWithSimplePublicProperty::class)
                ->addArgument('publicProperty', 'Ready'),
        ];

        $container = new DiContainer($definitions);

        $res = $container->call('\Tests\Fixtures\funcWithDependencyClass', ['append' => '🚀']);

        $this->assertEquals('Ready + 🚀', $res);
    }

    public function testUserFunctionWithDefaultValue(): void
    {
        $container = new DiContainer([
            diAutowire(ClassWithSimplePublicProperty::class)
                ->addArgument('publicProperty', 'I am alone'),
        ]);

        $res = $container->call('\Tests\Fixtures\funcWithDependencyClass');

        $this->assertEquals('I am alone', $res);
    }

    public function testUserFunctionInjectByAttributeWithDefaultValue(): void
    {
        $definitions = [
            diAutowire(ClassWithSimplePublicProperty::class),
            'vars.public-property' => 'Hello',
        ];

        $config = new DiContainerConfig(useAttribute: true);
        $container = new DiContainer($definitions, $config);

        $res = $container->call('\Tests\Fixtures\funcWithDependencyClass');

        $this->assertEquals('Hello', $res);
    }

    public function testUserFunctionInjectByAttribute(): void
    {
        $definitions = [
            diAutowire(ClassWithSimplePublicProperty::class),
            'vars.public-property' => 'Hello',
            'service.append' => '🌐',
        ];

        $config = new DiContainerConfig(useAttribute: true);
        $container = new DiContainer($definitions, $config);

        $res = $container->call('\Tests\Fixtures\funcWithDependencyClass');

        $this->assertEquals('Hello + 🌐', $res);
    }

    public function testUserFunctionVariadicArgumentsPassByCallMethod(): void
    {
        $container = new DiContainer([
            'item.first' => diAutowire(ClassWithSimplePublicProperty::class)
                ->addArgument('publicProperty', 'Hello'),
            'item.second' => diAutowire(ClassWithSimplePublicProperty::class)
                ->addArgument('publicProperty', 'World'),
        ]);

        $res = $container->call(
            '\Tests\Fixtures\functionWithVariadic',
            [
                'item' => [ // <-- wrap variadic argument
                    diReference('item.first'),
                    diReference('item.second'),
                ], // <-- wrap variadic argument
            ]
        );

        $this->assertEquals(' / Hello / World', $res);
    }

    public function testUserFunctionVariadicArgumentsByAttribute(): void
    {
        $definitions = [
            'item.first' => diAutowire(ClassWithSimplePublicProperty::class)
                ->addArgument('publicProperty', '🎈'),
            'item.second' => diAutowire(ClassWithSimplePublicProperty::class)
                ->addArgument('publicProperty', '🎃'),
        ];
        $config = new DiContainerConfig(useAttribute: true);
        $container = new DiContainer($definitions, $config);

        $res = $container->call('\Tests\Fixtures\functionWithVariadic');

        $this->assertEquals(' / 🎈 / 🎃', $res);
    }
}
