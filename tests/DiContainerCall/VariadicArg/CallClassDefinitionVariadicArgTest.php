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
use function Kaspi\DiContainer\diFactory;
use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject::getIdentifier
 * @covers \Kaspi\DiContainer\Attributes\InjectByCallable
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\diFactory
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Helper
 * @covers \Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition
 *
 * @internal
 */
class CallClassDefinitionVariadicArgTest extends TestCase
{
    public function testCallStaticMethodWithoutAttributePassArgumentBydiGet(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true,
            useAttribute: false
        );
        $container = new DiContainer(config: $config);

        $res = $container->call(
            [Talk::class, 'staticMethodByReference'],
            [
                'word' => diGet(WordSuffix::class),
                'word_2' => diGet(WordHello::class),
            ]
        );
        $this->assertInstanceOf(WordSuffix::class, $res['word']);
        $this->assertInstanceOf(WordHello::class, $res['word_2']);
    }

    public function testCallStaticMethodWithoutAttributePassArgumentByDiAutowire(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: false, // off autoconfigure.
            useAttribute: false // off attribute
        );
        $container = new DiContainer(config: $config);

        $res = $container->call(
            [Talk::class, 'staticMethodByReference'],
            [
                'word' => diAutowire(WordSuffix::class),
                'word1' => diAutowire(WordHello::class),
            ]
        );
        $this->assertInstanceOf(WordSuffix::class, $res['word']);
        $this->assertInstanceOf(WordHello::class, $res['word1']);
    }

    public function testCallStaticMethodWithoutAttributePassArgumentByReferenceOneToMany(): void
    {
        $config = new DiContainerConfig(
            useAttribute: false // off attribute for configure.
        );

        $container = new DiContainer(config: $config);

        $res = $container->call(
            [Talk::class, 'staticMethodByReference'],
            [
                'word' => $container->get(WordSuffix::class),
                'word2' => $container->get(WordHello::class),
            ]
        );
        $this->assertInstanceOf(WordSuffix::class, $res['word']);
        $this->assertInstanceOf(WordHello::class, $res['word2']);
    }

    public function testCallStaticMethodWitAttributeInjectIdAsContainerIdentifier(): void
    {
        $config = new DiContainerConfig(
            useAttribute: true // inject by attribute
        );
        $definitions = [
            'word.first' => diAutowire(WordHello::class),
            'word.second' => diAutowire(WordSuffix::class),
        ];
        $container = new DiContainer(definitions: $definitions, config: $config);

        $res = $container->call([Talk::class, 'staticMethodByReference']);

        $this->assertInstanceOf(WordHello::class, $res[0]);
        $this->assertInstanceOf(WordSuffix::class, $res[1]);
    }

    public function testCallStaticMethodWitAttributeInjectIdAsContainerIdentifierDiFactory(): void
    {
        $config = new DiContainerConfig(
            useAttribute: true, // inject by attribute
        );
        $definitions = [
            'services.words' => diFactory(WordVariadicDiFactory::class),
        ];
        $container = new DiContainer(definitions: $definitions, config: $config);

        $res = $container->call([Talk::class, 'staticMethodByReferenceOneToMany']);

        $this->assertInstanceOf(WordSuffix::class, $res[0]);
    }

    public function testCallStaticMethodWitAttributeInjectByCallable(): void
    {
        $config = new DiContainerConfig(
            useAttribute: true, // inject by attribute
        );
        $container = new DiContainer(config: $config);

        $res = $container->call([Talk::class, 'staticMethodByDiFactoryOneToMany']);

        $this->assertCount(1, $res);
        $this->assertInstanceOf(WordSuffix::class, $res[0]);
    }

    public function testCallStaticMethodWitAttributeInjectIdAsClass(): void
    {
        $config = new DiContainerConfig(
            useAttribute: true, // inject by attribute
        );
        $container = new DiContainer(config: $config);

        $res = $container->call([Talk::class, 'staticMethodByClass']);

        $this->assertInstanceOf(WordSuffix::class, $res[0]);
        $this->assertInstanceOf(WordHello::class, $res[1]);
    }

    public function testCallStaticMethodResolveWithoutArguments(): void
    {
        $config = new DiContainerConfig();
        $definitions = [
            'wordService' => diAutowire(WordVariadicDiFactory::class),
        ];
        $container = new DiContainer($definitions, $config);

        $res = $container->call([Talk::class, 'staticMethodByArgumentNameOneToMany']);

        self::assertEmpty($res);
    }
}
