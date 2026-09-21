<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use ArrayIterator;
use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DTO\PriorityBoundConfiguration;
use Kaspi\DiContainer\DTO\SetupArgumentBuilder;
use Kaspi\DiContainer\DTO\SetupTypeWithArguments;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\SetupAttributeTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\FooPrivateConstructor;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\FooSetup;

/**
 * @internal
 */
#[CoversClass(Autowire::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(Helper::class)]
#[UsesClass(SetupTypeWithArguments::class)]
#[UsesClass(SetupArgumentBuilder::class)]
#[UsesClass(DiContainerConfig::class)]
#[UsesClass(AttributeReader::class)]
#[UsesClass(SetupAttributeTrait::class)]
#[UsesClass(PriorityBoundConfiguration::class)]
class ExposeArgumentBuilderTest extends TestCase
{
    #[DataProvider('exposeArgumentBuilderExceptionProvider')]
    public function testExposeArgumentBuilderException(string $class, string $expectMessage): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage($expectMessage);

        (new DiDefinitionAutowire($class))
            ->exposeArgumentBuilder($this->createMock(DiContainerInterface::class))
        ;
    }

    #[DataProvider('exposeArgumentBuilderExceptionProvider')]
    public function testExposeSetupArgumentBuildersException(string $class, string $expectMessage): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage($expectMessage);

        (new DiDefinitionAutowire($class))
            ->exposeSetupArgumentBuilders($this->createMock(DiContainerInterface::class))
        ;
    }

    public static function exposeArgumentBuilderExceptionProvider(): Generator
    {
        yield 'private constructor' => [FooPrivateConstructor::class, 'class is not instantiable.'];

        yield 'clas not found' => ['Foo', 'Class "Foo" does not exist'];
    }

    #[DataProvider('exposeSetupArgumentBuildersMethodProvider')]
    public function testExposeSetupArgumentBuildersMethod(string $class, string $method, string $expectMessageMatches): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches($expectMessageMatches);

        (new DiDefinitionAutowire($class))
            ->setup($method)
            ->exposeSetupArgumentBuilders($this->createMock(DiContainerInterface::class))
        ;
    }

    public static function exposeSetupArgumentBuildersMethodProvider(): Generator
    {
        yield 'method not exist' => [FooSetup::class, 'baz', '/The setter method ".+FooSetup::baz\(\)" does not exist\./'];

        yield 'method __construct' => [FooSetup::class, '__construct', '/Cannot use ".+FooSetup::__construct\(\)" as setter/'];

        yield 'method __destruct' => [FooSetup::class, '__destruct', '/Cannot use ".+FooSetup::__destruct\(\)" as setter/'];

        yield 'class not exist' => ['Foo', 'bar', '/Class "Foo" does not exist/'];
    }

    public function testExposeArgumentBuilderWithContext(): void
    {
        $class = new class(new ArrayIterator([])) {
            public function __construct(ArrayIterator $iterator) {}
        };

        $autowire = new Autowire(arguments: ['foo', 'bar']);
        $def = new DiDefinitionAutowire($class::class, priorityBoundConfiguration: new PriorityBoundConfiguration($autowire->arguments, $autowire->setups, $autowire->tags));

        $argBuilder = $def->exposeArgumentBuilder($this->createMock(DiContainerInterface::class));

        self::assertEquals(['foo', 'bar'], $argBuilder->build());
    }

    #[DataProvider('exposeArgumentBuilderWithContextProvider')]
    public function testExposeSetupArgumentBuilderWithContext(string $class, ?Autowire $context, int $expectSetupCount): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $priorityBoundConfiguration = null !== $context
            ? new PriorityBoundConfiguration($context->arguments, $context->setups, $context->tags)
            : null;
        $def = new DiDefinitionAutowire($class, priorityBoundConfiguration: $priorityBoundConfiguration);

        $argBuilders = $def->exposeSetupArgumentBuilders($mockContainer);

        self::assertCount($expectSetupCount, $argBuilders);
    }

    public static function exposeArgumentBuilderWithContextProvider(): Generator
    {
        $class = new class(new ArrayIterator([])) {
            public function __construct(ArrayIterator $iterator) {}

            #[Setup]
            public function doSetup(): void {}
        };

        yield [
            $class::class,
            new Autowire(arguments: ['foo', 'bar'], setups: []),
            0,
        ];

        yield [
            $class::class,
            null,
            1,
        ];
    }
}
