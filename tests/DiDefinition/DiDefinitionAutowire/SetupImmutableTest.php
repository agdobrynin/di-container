<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Enum\SetupConfigureMethod;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\LazyDefinitionIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
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
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(\Kaspi\DiContainer\Attributes\SetupImmutable::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionTaggedAs::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(SetupConfigureMethod::class)]
#[CoversClass(Helper::class)]
#[CoversClass(LazyDefinitionIterator::class)]
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

    #[DataProvider('dataProviderImmutableSuccess')]
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

    public static function dataProviderImmutableSuccess(): Generator
    {
        yield 'for method withSomeClassClonedReturnSelf' => ['withSomeClassClonedReturnSelf'];

        yield 'for method withSomeClassClonedReturnSameClass' => ['withSomeClassClonedReturnSameClass'];

        yield 'for method withSomeClassClonedNotReturnTypehint' => ['withSomeClassClonedNotReturnTypehint'];

        yield 'for method withSomeClassNotClonedReturnSelf' => ['withSomeClassNotClonedReturnSelf'];
    }

    #[DataProvider('dataProviderImmutableFail')]
    public function testImmutableFail(string $method): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/The immutable setter .+SetupImmutable::'.$method.'\(\)" must return same class/');

        $def = (new DiDefinitionAutowire(SetupImmutable::class, isSingleton: true))
            ->setupImmutable($method) // argument $someClass resolve by typehint.
        ;

        $def->resolve($this->mockContainer);
    }

    public static function dataProviderImmutableFail(): Generator
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

    #[DataProvider('dataProviderSetupOnMethod')]
    public function testSetupOnMethod(string $class, string $method): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot use ".+'.$method.'\(\)" as setter/');

        $def = (new DiDefinitionAutowire($class))
            ->setupImmutable($method)
        ;

        $def->resolve($this->mockContainer);
    }

    public static function dataProviderSetupOnMethod(): Generator
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
