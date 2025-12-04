<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionFactory\Fixtures\FooPrivateConstructor;
use Tests\DiDefinition\DiDefinitionFactory\Fixtures\FooSetup;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionFactory
 * @covers \Kaspi\DiContainer\Helper
 *
 * @internal
 */
class ExposeArgumentBuilderTestTest extends TestCase
{
    public function testExposeArgumentBuilderException(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('class is not instantiable.');

        (new DiDefinitionFactory(FooPrivateConstructor::class))
            ->exposeArgumentBuilder($this->createMock(DiContainerInterface::class))
        ;
    }

    public function testExposeSetupArgumentBuildersException(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('class is not instantiable.');

        (new DiDefinitionFactory(FooPrivateConstructor::class))
            ->exposeSetupArgumentBuilders($this->createMock(DiContainerInterface::class))
        ;
    }

    /**
     * @dataProvider exposeSetupArgumentBuildersMethodProvider
     */
    public function testExposeSetupArgumentBuildersMethod(string $class, string $method, string $expectMessageMatches): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches($expectMessageMatches);

        (new DiDefinitionAutowire($class))
            ->setup($method)
            ->exposeSetupArgumentBuilders($this->createMock(DiContainerInterface::class))
        ;
    }

    public function exposeSetupArgumentBuildersMethodProvider(): Generator
    {
        yield 'method not exist' => [FooSetup::class, 'baz', '/The setter method ".+FooSetup::baz\(\)" does not exist\./'];

        yield 'method __construct' => [FooSetup::class, '__construct', '/Cannot use ".+FooSetup::__construct\(\)" as setter/'];

        yield 'method __destruct' => [FooSetup::class, '__destruct', '/Cannot use ".+FooSetup::__destruct\(\)" as setter/'];
    }
}
