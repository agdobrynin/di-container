<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\VariadicArg;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\DefinitionDiCall;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition;
use Kaspi\DiContainer\SourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\DiContainerCall\VariadicArg\Fixtures\Talk;
use Tests\DiContainerCall\VariadicArg\Fixtures\WordHello;
use Tests\DiContainerCall\VariadicArg\Fixtures\WordSuffix;
use Tests\DiContainerCall\VariadicArg\Fixtures\WordVariadicDiFactory;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diFactory;
use function Kaspi\DiContainer\diGet;

/**
 * @covers
 *
 * @internal
 */
#[
    CoversFunction('\Kaspi\DiContainer\diAutowire'),
    CoversFunction('\Kaspi\DiContainer\diGet'),
    CoversFunction('\Kaspi\DiContainer\diFactory'),
    CoversClass(AttributeReader::class),
    CoversClass(Inject::class),
    CoversClass(ReflectionMethodByDefinition::class),
    CoversClass(Helper::class),
    CoversClass(DiDefinitionGet::class),
    CoversClass(DiDefinitionFactory::class),
    CoversClass(DiDefinitionCallable::class),
    CoversClass(DiDefinitionAutowire::class),
    CoversClass(ArgumentResolver::class),
    CoversClass(ArgumentBuilder::class),
    CoversClass(DiContainerConfig::class),
    CoversClass(DiContainer::class),
    CoversClass(DefinitionDiCall::class),
    CoversClass(InjectByCallable::class),
    CoversClass(SourceDefinitionsMutable::class),
]
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
