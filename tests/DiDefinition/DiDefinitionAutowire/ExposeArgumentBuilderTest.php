<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\FooPrivateConstructor;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\FooSetup;

/**
 * @internal
 */
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(Helper::class)]
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
}
