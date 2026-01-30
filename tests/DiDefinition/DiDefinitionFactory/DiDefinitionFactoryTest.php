<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionFactory;

use Generator;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionFactory\Fixtures\FooFactory;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversFunction('Kaspi\DiContainer\diAutowire')]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(DiDefinitionFactory::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(Helper::class)]
class DiDefinitionFactoryTest extends TestCase
{
    #[DataProvider('dataProviderGetDefinitionSuccess')]
    public function testGetDefinitionSuccess(array|string $definition, array $expectDefinition, string $method): void
    {
        $factory = new DiDefinitionFactory($definition);
        self::assertEquals($expectDefinition, $factory->getDefinition());
        self::assertEquals($method, $factory->getFactoryMethod());
        self::assertNull($factory->isSingleton());
    }

    public static function dataProviderGetDefinitionSuccess(): Generator
    {
        yield 'class-string and __invoke' => [
            Fixtures\Foo::class,
            ['Tests\DiDefinition\DiDefinitionFactory\Fixtures\Foo', '__invoke'],
            '__invoke',
        ];

        yield 'container identifier and __invoke' => [
            'services.foo',
            ['services.foo', '__invoke'],
            '__invoke',
        ];

        yield 'class-string and method' => [
            [FooFactory::class, 'make'],
            ['Tests\DiDefinition\DiDefinitionFactory\Fixtures\FooFactory', 'make'],
            'make',
        ];

        yield 'class-string with method divided semicolumn' => [
            'Tests\DiDefinition\DiDefinitionFactory\Fixtures\FooFactory::make',
            ['Tests\DiDefinition\DiDefinitionFactory\Fixtures\FooFactory', 'make'],
            'make',
        ];

        yield 'safe class-string with concat method divided semicolumn' => [
            FooFactory::class.'::make',
            ['Tests\DiDefinition\DiDefinitionFactory\Fixtures\FooFactory', 'make'],
            'make',
        ];

        yield 'container identifier and method' => [
            ['services.foo_factory', 'make'],
            ['services.foo_factory', 'make'],
            'make',
        ];
    }

    #[DataProvider('dataProviderGetDefinitionException')]
    public function testGetDefinitionException(array|string $definition): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        (new DiDefinitionFactory($definition))->getDefinition();
    }

    public static function dataProviderGetDefinitionException(): Generator
    {
        yield 'empty string' => [''];

        yield 'empty array' => [[]];

        yield 'array with first empty' => [['']];

        yield 'array with first and second empty' => [['', '']];

        yield 'array with first is ok and second empty' => [['foo', '']];

        yield 'array with first empty and second ok' => [['', 'foo']];
    }

    public function testExposeFactoryMethodArgBuilderCannotGetDefinition(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot get factory constructor via container definition "Tests\DiDefinition\DiDefinitionFactory\Fixtures\Foo"');

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('getDefinition')
            ->with('Tests\DiDefinition\DiDefinitionFactory\Fixtures\Foo')
            ->willThrowException(new NotFoundException())
        ;

        (new DiDefinitionFactory(Fixtures\Foo::class))
            ->exposeFactoryMethodArgumentBuilder($containerMock)
        ;
    }

    public function testExposeFactoryMethodArgBuilderGetDefinitionNoneAutowire(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('The factory constructor was obtained through the container identifier "services.foo"');

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('getDefinition')
            ->with('services.foo')
            ->willReturn(new DiDefinitionValue('foo'))
        ;

        (new DiDefinitionFactory('services.foo'))
            ->exposeFactoryMethodArgumentBuilder($containerMock)
        ;
    }

    public function testExposeFactoryMethodArgBuilderFactoryMethodNotExist(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot get the factory method');

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('getDefinition')
            ->with('Tests\DiDefinition\DiDefinitionFactory\Fixtures\Foo')
            ->willReturn(new DiDefinitionAutowire(Fixtures\Foo::class))
        ;

        (new DiDefinitionFactory([Fixtures\Foo::class, 'methodNotExist']))
            ->exposeFactoryMethodArgumentBuilder($containerMock)
        ;
    }

    public function testExposeFactoryMethodArgBuilderFactoryMethodIsNotPublic(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('FooFactoryMethodNotPublic::make() must be declared with public modifier');

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('getDefinition')
            ->with('Tests\DiDefinition\DiDefinitionFactory\Fixtures\FooFactoryMethodNotPublic')
            ->willReturn(new DiDefinitionAutowire(Fixtures\FooFactoryMethodNotPublic::class))
        ;

        (new DiDefinitionFactory([Fixtures\FooFactoryMethodNotPublic::class, 'make']))
            ->exposeFactoryMethodArgumentBuilder($containerMock)
        ;
    }

    public function testExposeFactoryMethodArgBuilderSuccess(): void
    {
        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('getDefinition')
            ->with('Tests\DiDefinition\DiDefinitionFactory\Fixtures\Foo')
            ->willReturn(new DiDefinitionAutowire(Fixtures\Foo::class))
        ;

        $def = new DiDefinitionFactory(Fixtures\Foo::class);

        self::assertEquals('__invoke', $def->exposeFactoryMethodArgumentBuilder($containerMock)->getFunctionOrMethod()->getName());
        // check cached DiDefinitionFactory::$factoryMethodArgumentBuilder
        self::assertFalse($def->exposeFactoryMethodArgumentBuilder($containerMock)->getFunctionOrMethod()->isStatic());
    }

    public function testBindArgumentsOnStaticFactory(): void
    {
        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('getDefinition')
            ->with('Tests\DiDefinition\DiDefinitionFactory\Fixtures\FooFactoryStatic')
            ->willReturn(new DiDefinitionAutowire(Fixtures\FooFactoryStatic::class))
        ;

        $factory = new DiDefinitionFactory([Fixtures\FooFactoryStatic::class, 'make']);
        $factory->bindArguments(
            diAutowire(Fixtures\Bar::class)
                ->bindArguments(str: 'Lorem ipsum')
        );

        $res = $factory->resolve($containerMock);

        self::assertInstanceOf(Fixtures\Bar::class, $res);
        self::assertEquals('Lorem ipsum', $res->str);
    }

    public function testResolveNoneStaticFactory(): void
    {
        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('getDefinition')
            ->with('Tests\DiDefinition\DiDefinitionFactory\Fixtures\FooFactory')
            ->willReturn(new DiDefinitionAutowire(FooFactory::class))
        ;
        $containerMock->method('get')
            ->with('Tests\DiDefinition\DiDefinitionFactory\Fixtures\FooFactory')
            ->willReturn(
                new FooFactory(
                    new Fixtures\Bar('Lorem ipsum')
                )
            )
        ;

        $factory = new DiDefinitionFactory(FooFactory::class.'::make');

        self::assertEquals('Lorem ipsum', $factory->resolve($containerMock));
    }

    public function testResolveConstructorNoneStaticFactoryWithException(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot resolve factory constructor via container identifier "Tests\DiDefinition\DiDefinitionFactory\Fixtures\FooFactory"');

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('getDefinition')
            ->with('Tests\DiDefinition\DiDefinitionFactory\Fixtures\FooFactory')
            ->willReturn(new DiDefinitionAutowire(FooFactory::class))
        ;
        $containerMock->method('get')
            ->with('Tests\DiDefinition\DiDefinitionFactory\Fixtures\FooFactory')
            ->willThrowException(new ContainerException())
        ;

        $factory = new DiDefinitionFactory(FooFactory::class.'::make');

        $factory->resolve($containerMock);
    }
}
