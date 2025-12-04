<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Generator;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\LazyDefinitionIterator;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\ClassWithConstructDestruct;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\Foo;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\FooBar;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SetupImmutable;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SetupImmutableByAttribute;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SetupImmutableByAttributeWithArgumentAsDefinition;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SomeClass;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\SetupImmutable
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Enum\SetupConfigureMethod
 * @covers \Kaspi\DiContainer\Helper
 * @covers \Kaspi\DiContainer\LazyDefinitionIterator
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
        ;

        /** @var SetupImmutable $setupImmutableClass */
        $setupImmutableClass = $def->resolve($this->mockContainer);

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
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/The immutable setter .+SetupImmutable::'.$method.'\(\)" must return same class/');

        $def = (new DiDefinitionAutowire(SetupImmutable::class, isSingleton: true))
            ->setupImmutable($method) // argument $someClass resolve by typehint.
        ;

        $def->resolve($this->mockContainer);
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

        $def = (new DiDefinitionAutowire(SetupImmutableByAttribute::class));

        $setupImmutableClass = $def->resolve($this->mockContainer);

        self::assertInstanceOf(SomeClass::class, $setupImmutableClass->getSomeClass());
    }

    public function testSetupImmutableByAttributeWithoutConfigUseAttribute(): void
    {
        $this->mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: false))
        ;

        $def = (new DiDefinitionAutowire(SetupImmutableByAttribute::class));

        $setupImmutableClass = $def->resolve($this->mockContainer);

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
        ;

        $setupImmutableClass = $def->resolve($this->mockContainer);

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

        $def = (new DiDefinitionAutowire(SetupImmutableByAttributeWithArgumentAsDefinition::class))
            ->setupImmutable('withSomeClassAsContainerIdentifier', someClass: null) // overrode by php attribute on method
        ;

        /** @var SetupImmutableByAttributeWithArgumentAsDefinition $class */
        $class = $def->resolve($mockContainer);

        self::assertInstanceOf(SomeClass::class, $class->getSomeClass());
        self::assertEquals('foo', $class->getSomeClass()->getValue());

        self::assertEquals('string from container', $class->getAnyAsContainerIdentifier());
        self::assertEquals('@la-la-la', $class->getAnyAsEscapedString());
        self::assertEquals('any_string', $class->getAnyAsString());
        self::assertInstanceOf(LazyDefinitionIterator::class, $class->getRules());
    }

    /**
     * @dataProvider dataProviderSetupOnMethod
     */
    public function testSetupOnMethod(string $class, string $method): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot use ".+'.$method.'\(\)" as setter/');

        $def = (new DiDefinitionAutowire($class))
            ->setupImmutable($method)
        ;

        $def->resolve($this->mockContainer);
    }

    public function dataProviderSetupOnMethod(): Generator
    {
        yield 'on construct setup method' => [ClassWithConstructDestruct::class, '__construct'];

        yield 'on destruct setup method' => [ClassWithConstructDestruct::class, '__destruct'];
    }

    public function testResolveFailBindNamedArgumentWithoutAttribute(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter by named argument \$rule in.+Foo::method()/');

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: false))
        ;

        $mockContainer->method('get')
            ->willReturnCallback(static function (string $id) {
                if ('services.secure_string' === $id) {
                    return 'secure_string';
                }

                throw new NotFoundException();
            })
        ;

        $def = (new DiDefinitionAutowire(Foo::class))
            ->setupImmutable(
                'method',
                diGet('services.secure_string'),
                rule: diGet('services.rule_a')
            )
        ;

        $def->resolve($mockContainer);
    }

    public function testResolveFailBindNamedArgumentByAttribute(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter by named argument \$rule in.+FooBar::method()/');

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $mockContainer->method('get')
            ->willReturnCallback(static function (string $id) {
                if ('services.secure_string' === $id) {
                    return 'secure_string';
                }

                throw new NotFoundException();
            })
        ;

        (new DiDefinitionAutowire(FooBar::class))
            ->resolve($mockContainer)
        ;
    }
}
