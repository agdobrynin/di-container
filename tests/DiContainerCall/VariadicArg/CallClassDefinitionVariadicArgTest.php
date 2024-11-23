<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\VariadicArg;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Tests\DiContainerCall\VariadicArg\Fixtures\Talk;
use Tests\DiContainerCall\VariadicArg\Fixtures\WordHello;
use Tests\DiContainerCall\VariadicArg\Fixtures\WordSuffix;
use Tests\DiContainerCall\VariadicArg\Fixtures\WordVariadicDiFactory;

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
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
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

    public function testCallStaticMethodWithoutAttributePassArgumentByReferenceOneToMany(): void
    {
        $config = new DiContainerConfig(
            useAttribute: false // off attribute for configure.
        );
        $container = new DiContainer(config: $config);
        // one definition return array of resolved definition.
        $container->set('service.word', diAutowire(WordVariadicDiFactory::class));

        $res = $container->call(
            [Talk::class, 'staticMethodByReference'],
            [
                'word' => diReference('service.word'),
            ]
        );
        $this->assertInstanceOf(WordSuffix::class, $res[0]);
        $this->assertInstanceOf(WordHello::class, $res[1]);
    }

    public function testCallStaticMethodWithoutAttributePassArgumentByDiFactoryOneToMany(): void
    {
        $config = new DiContainerConfig(
            useAttribute: false // off attribute for configure.
        );
        $container = new DiContainer(config: $config);

        $res = $container->call(
            [Talk::class, 'staticMethodByReference'],
            [
                'word' => diAutowire(WordVariadicDiFactory::class),
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

    public function testCallStaticMethodWitAttributeInjectIdAsContainerIdentifierOneToMany(): void
    {
        $config = new DiContainerConfig();
        $definitions = [
            'services.words' => diAutowire(WordVariadicDiFactory::class),
        ];
        $container = new DiContainer(definitions: $definitions, config: $config);

        $res = $container->call([Talk::class, 'staticMethodByReferenceOneToMany']);

        $this->assertInstanceOf(WordSuffix::class, $res[0]);
        $this->assertInstanceOf(WordHello::class, $res[1]);
    }

    public function testCallStaticMethodWitAttributeInjectIdAsContainerAsClassOneToMany(): void
    {
        $config = new DiContainerConfig();
        $container = new DiContainer(config: $config);

        $res = $container->call([Talk::class, 'staticMethodByDiFactoryOneToMany']);

        $this->assertInstanceOf(WordSuffix::class, $res[0]);
        $this->assertInstanceOf(WordHello::class, $res[1]);
    }

    public function testCallStaticMethodWitAttributeInjectIdAsClass(): void
    {
        $config = new DiContainerConfig();
        $container = new DiContainer(config: $config);

        $res = $container->call([Talk::class, 'staticMethodByClass']);

        $this->assertInstanceOf(WordSuffix::class, $res[0]);
        $this->assertInstanceOf(WordHello::class, $res[1]);
    }

    public function testCallStaticMethodResolveByArgumentName(): void
    {
        $config = new DiContainerConfig();
        $definitions = [
            'wordService' => diAutowire(WordVariadicDiFactory::class),
        ];
        $container = new DiContainer($definitions, $config);

        $res = $container->call([Talk::class, 'staticMethodByDiArgumentNameOneToMany']);

        $this->assertInstanceOf(WordSuffix::class, $res[0]);
        $this->assertInstanceOf(WordHello::class, $res[1]);
    }
}
