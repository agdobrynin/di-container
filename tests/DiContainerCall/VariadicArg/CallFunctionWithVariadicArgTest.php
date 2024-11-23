<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\VariadicArg;

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
 * @covers \Kaspi\DiContainer\diReference
 *
 * @internal
 */
class CallFunctionWithVariadicArgTest extends TestCase
{
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


    public function testUserFunctionVariadicArgumentsConfigToOneToMany(): void
    {

    }

    public function testUserFunctionVariadicArgumentsByInjectWithIdAsReferenceToOneToMany(): void
    {

    }

    public function testUserFunctionVariadicArgumentsByInjectWithIdAsDiFactoryOneToMany(): void
    {

    }
}
