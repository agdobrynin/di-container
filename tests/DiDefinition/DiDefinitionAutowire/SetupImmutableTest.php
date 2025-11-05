<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Generator;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\ClassWithConstructDestruct;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SetupImmutable;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SetupImmutableByAttribute;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SetupImmutableByAttributeWithArgumentAsReference;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SomeClass;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\Attributes\SetupImmutable
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
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

    public function dataProviderImmutableSuccess(): Generator
    {
        yield 'for method withSomeClassClonedReturnSelf' => ['withSomeClassClonedReturnSelf'];

        yield 'for method withSomeClassClonedReturnSameClass' => ['withSomeClassClonedReturnSameClass'];

        yield 'for method withSomeClassClonedNotReturnTypehint' => ['withSomeClassClonedNotReturnTypehint'];

        yield 'for method withSomeClassNotClonedReturnSelf' => ['withSomeClassNotClonedReturnSelf'];
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

    public function dataProviderImmutableFail(): Generator
    {
        yield 'for method withSomeClassFailReturnType' => ['withSomeClassFailReturnType'];

        yield 'for method withSomeClassFailReturnObject' => ['withSomeClassFailReturnObject'];

        yield 'for method withSomeClassFailReturnTypehintVoid' => ['withSomeClassFailReturnTypehintVoid'];
    }

    public function testSetupImmutableByAttribute(): void
    {
        $this->mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $def = (new DiDefinitionAutowire(SetupImmutableByAttribute::class))
            ->setContainer($this->mockContainer)
        ;

        $setupImmutableClass = $def->invoke();

        self::assertInstanceOf(SomeClass::class, $setupImmutableClass->getSomeClass());
    }

    public function testSetupImmutableByAttributeWithoutConfigUseAttribute(): void
    {
        $this->mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: false))
        ;

        $def = (new DiDefinitionAutowire(SetupImmutableByAttribute::class))
            ->setContainer($this->mockContainer)
        ;

        $setupImmutableClass = $def->invoke();

        self::assertNull($setupImmutableClass->getSomeClass());
    }

    public function testSetupImmutableByAttributeWithOverrideDefinitionSetup(): void
    {
        $this->mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $def = (new DiDefinitionAutowire(SetupImmutableByAttribute::class))
            ->setupImmutable('withSomeClass', someClass: null)
            ->setupImmutable('withSomeClass', someClass: diAutowire(SomeClass::class)->bindArguments('aaa'))

            ->setContainer($this->mockContainer)
        ;

        $setupImmutableClass = $def->invoke();

        self::assertInstanceOf(SomeClass::class, $setupImmutableClass->getSomeClass());
        self::assertNull($setupImmutableClass->getSomeClass()->getValue());
    }

    public function testSetupImmutableByAttributeStringArgumentAsDiGet(): void
    {
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;
        $mockContainer->method('get')
            ->willReturnMap([
                ['services.some_class', new SomeClass('foo')],
                ['services.any_string', 'string from container'],
            ])
        ;

        $def = (new DiDefinitionAutowire(SetupImmutableByAttributeWithArgumentAsReference::class))
            ->setupImmutable('withSomeClassAsContainerIdentifier', someClass: null) // overrode by php attribute on method
            ->setContainer($mockContainer)
        ;

        /** @var SetupImmutableByAttributeWithArgumentAsReference $class */
        $class = $def->invoke();

        self::assertInstanceOf(SomeClass::class, $class->getSomeClass());
        self::assertEquals('foo', $class->getSomeClass()->getValue());

        self::assertEquals('string from container', $class->getAnyAsContainerIdentifier());
        self::assertEquals('@la-la-la', $class->getAnyAsEscapedString());
        self::assertEquals('any_string', $class->getAnyAsString());
    }

    /**
     * @dataProvider dataProviderSetupOnMethod
     */
    public function testSetupOnMethod(string $class, string $method): void
    {
        $def = (new DiDefinitionAutowire($class))
            ->setupImmutable($method)
            ->setContainer($this->createMock(DiContainerInterface::class))
        ;

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot use.+'.$method.'\(\) as setter/');

        $def->invoke();
    }

    public function dataProviderSetupOnMethod(): Generator
    {
        yield 'on construct setup method' => [ClassWithConstructDestruct::class, '__construct'];

        yield 'on destruct setup method' => [ClassWithConstructDestruct::class, '__destruct'];
    }
}
