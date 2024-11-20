<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Attributes\VariadicSimpleArgumentsByInject;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\InjectByReference
 * @covers \Kaspi\DiContainer\Attributes\InjectContext
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 *
 * @internal
 */
class VariadicParametersByInjectTest extends TestCase
{
    public function testVariadicSimpleParametersInConstructor(): void
    {
        $c = (new DiContainerFactory())->make([
            'messages.welcome' => ['Hi there!', 'Lets play'],
        ]);

        $class = $c->get(VariadicSimpleArgumentsByInject::class);
        $this->assertEquals(['Hi there!', 'Lets play'], $class->sayHello);
    }

    public function testVariadicSimpleParametersInConstructorAndInMethod(): void
    {
        $container = (new DiContainerFactory())->make([
            'messages.welcome' => ['Hi there!', 'Lets play'],
            'messages.icon' => ['🎈', '🎉'],
        ]);

        $res = $container->call([VariadicSimpleArgumentsByInject::class, 'say']);

        $this->assertEquals('Hi there!_Lets play | 🎈 🎉', $res);
    }

    public function testVariadicSimpleParametersStaticMethod(): void
    {
        $container = (new DiContainerFactory())->make([
            'messages.welcome' => ['Hi there!', 'Lets play'],
            'messages.icon' => ['🎈', '🎉'],
        ]);

        $res = $container->call([VariadicSimpleArgumentsByInject::class, 'sayStatic']);

        $this->assertEquals('🎈~🎉', $res);
    }
}
