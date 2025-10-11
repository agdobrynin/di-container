<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SetupImmutable;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SomeClass;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class SetupImmutableTest extends TestCase
{
    private DiContainerInterface $mockContainer;

    public function setUp(): void
    {
        $this->mockContainer = $this->createMock(DiContainerInterface::class);
        $this->mockContainer->method('get')
            ->with(SomeClass::class)
            ->willReturn(new SomeClass())
        ;
    }

    public function dataProviderImmutableSuccess(): Generator
    {
        yield 'for method withSomeClassClonedReturnSelf' => ['withSomeClassClonedReturnSelf'];

        yield 'for method withSomeClassClonedReturnSameClass' => ['withSomeClassClonedReturnSameClass'];

        yield 'for method withSomeClassClonedNotReturnTypehint' => ['withSomeClassClonedNotReturnTypehint'];

        yield 'for method withSomeClassNotClonedReturnSelf' => ['withSomeClassNotClonedReturnSelf'];
    }

    /**
     * @dataProvider dataProviderImmutableSuccess
     */
    public function testImmutableSuccess(string $method): void
    {
        $def = (new DiDefinitionAutowire(SetupImmutable::class, isSingleton: true))
            ->setupImmutable($method) // argument $someClass resolve by typehint.
            ->setContainer($this->mockContainer)
        ;

        /** @var SetupImmutable $setupImmutableClass */
        $setupImmutableClass = $def->invoke();

        self::assertInstanceOf(SetupImmutable::class, $setupImmutableClass);
        self::assertInstanceOf(SomeClass::class, $setupImmutableClass->getSomeClass());
    }

    public function dataProviderImmutableFail(): Generator
    {
        yield 'for method withSomeClassFailReturnType' => ['withSomeClassFailReturnType'];

        yield 'for method withSomeClassFailReturnObject' => ['withSomeClassFailReturnObject'];

        yield 'for method withSomeClassFailReturnTypehintVoid' => ['withSomeClassFailReturnTypehintVoid'];
    }

    /**
     * @dataProvider dataProviderImmutableFail
     */
    public function testImmutableFail(string $method): void
    {
        $def = (new DiDefinitionAutowire(SetupImmutable::class, isSingleton: true))
            ->setupImmutable($method) // argument $someClass resolve by typehint.
            ->setContainer($this->mockContainer)
        ;

        $this->expectException(AutowireException::class);
        $this->expectExceptionMessageMatches('/The immutable setter .+SetupImmutable::'.$method.'\(\)" must return same class/');

        /** @var SetupImmutable $setupImmutableClass */
        $setupImmutableClass = $def->invoke();
    }
}
