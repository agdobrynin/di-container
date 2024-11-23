<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\VariadicArg;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Tests\DiContainerCall\VariadicArg\Fixtures\Talk;
use Tests\DiContainerCall\VariadicArg\Fixtures\WordHello;
use Tests\DiContainerCall\VariadicArg\Fixtures\WordSuffix;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject::getIdentifier
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
class CallClassDefinitionVariadicArgTest extends TestCase
{
    public function testCallStaticMethodWithoutAttributePassArgumentByDiReference(): void
    {
        $config = new DiContainerConfig(useZeroConfigurationDefinition: true, useAttribute: false);
        $container = new DiContainer(config: $config);

        $res = $container->call(
            [Talk::class, 'staticMethodByReference'],
            [
                'word' => [ // <- variadic vars wrap by array
                    diReference(WordSuffix::class),
                    diReference(WordHello::class),
                ], // <- variadic vars wrap by array
            ]
        );
        $this->assertInstanceOf(WordSuffix::class, $res[0]);
        $this->assertInstanceOf(WordHello::class, $res[1]);
    }

    public function testCallStaticMethodWithoutAttributePassArgumentByDiAutowire(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: false, // off autoconfigure.
            useAttribute: false
        );
        $container = new DiContainer(config: $config);

        $res = $container->call(
            [Talk::class, 'staticMethodByReference'],
            [
                'word' => [ // <- variadic vars wrap by array
                    diAutowire(WordSuffix::class),
                    diAutowire(WordHello::class),
                ], // <- variadic vars wrap by array
            ]
        );
        $this->assertInstanceOf(WordSuffix::class, $res[0]);
        $this->assertInstanceOf(WordHello::class, $res[1]);
    }

    public function testCallStaticMethodWitAttributeInjectIdAsContainerIdentifier(): void
    {
        $config = new DiContainerConfig();
        $definitions = [
            'word.first' => diAutowire(WordHello::class),
            'word.second' => diAutowire(WordSuffix::class),
        ];
        $container = new DiContainer(definitions: $definitions, config: $config);

        $res = $container->call([Talk::class, 'staticMethodByReference']);

        $this->assertInstanceOf(WordHello::class, $res[0]);
        $this->assertInstanceOf(WordSuffix::class, $res[1]);
    }

    public function testCallStaticMethodWitAttributeInjectIdAsClass(): void
    {
        $config = new DiContainerConfig();
        $container = new DiContainer(config: $config);

        $res = $container->call([Talk::class, 'staticMethodByClass']);

        $this->assertInstanceOf(WordSuffix::class, $res[0]);
        $this->assertInstanceOf(WordHello::class, $res[1]);
    }
}
