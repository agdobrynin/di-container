<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use PHPUnit\Framework\TestCase;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;

/**
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class ParameterResolveByTypeOrArgumentNameTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use DiContainerTrait;

    public function testParameterResolveByType(): void
    {
        $fn = static fn (\ArrayIterator $array) => $array;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with(\ArrayIterator::class)
            ->willReturn(new \ArrayIterator())
        ;
        $this->setContainer($mockContainer);

        $this->assertInstanceOf(
            \ArrayIterator::class,
            \call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters))
        );
    }

    public function testParameterResolveByName(): void
    {
        $fn = static fn ($myArrayIterator) => $myArrayIterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with('myArrayIterator')
            ->willReturn(new \ArrayIterator())
        ;
        $this->setContainer($mockContainer);

        $this->assertInstanceOf(
            \ArrayIterator::class,
            \call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters))
        );
    }

    public function testParameterResolveByNameVariadicParameterString(): void
    {
        $fn = static fn (SuperClass $superClass, string ...$word) => [$superClass, $word];
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer
            ->expects(self::exactly(2))
            ->method('get')
            ->with($this->logicalOr(
                SuperClass::class,
                'word'
            ))
            ->willReturn(
                new SuperClass(),
                'one'
            )
        ;

        $this->setContainer($mockContainer);

        $params = $this->resolveParameters([], $reflectionParameters);

        $this->assertCount(2, $params);
        $this->assertInstanceOf(SuperClass::class, $params[0]);
        $this->assertEquals('one', $params[1]);

        $this->assertInstanceOf(SuperClass::class, \call_user_func_array($fn, $params)[0]);
        $this->assertEquals(['one'], \call_user_func_array($fn, $params)[1]);
    }

    public function testParameterResolveByNameVariadicParameterArray(): void
    {
        $fn = static fn (array ...$phrase) => $phrase;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer
            ->expects($this->once())
            ->method('get')
            ->with('phrase')
            ->willReturn(
                ['one', 'two', 'three'],
            )
        ;

        $this->setContainer($mockContainer);

        $params = $this->resolveParameters([], $reflectionParameters);

        $this->assertCount(1, $params);
        $this->assertEquals(
            [['one', 'two', 'three']],
            \call_user_func_array($fn, $params)
        );
    }

    public function testParameterResolveByNameNonVariadicParameterArray(): void
    {
        $fn = static fn (array $phrase) => $phrase;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer
            ->expects($this->once())
            ->method('get')
            ->with('phrase')
            ->willReturn(
                ['one', 'two', 'three'],
            )
        ;

        $this->setContainer($mockContainer);

        $params = $this->resolveParameters([], $reflectionParameters);

        $this->assertEquals(
            ['one', 'two', 'three'],
            \call_user_func_array($fn, $params)
        );
    }

    public function testParameterResolveByTypeNotFoundAndSetDefaultValue(): void
    {
        $fn = static fn (?SomeClass $someClass = null) => $someClass;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with(SomeClass::class)
            ->willThrowException(new NotFoundException())
        ;
        $this->setContainer($mockContainer);

        $this->assertNull(\call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters)));
    }

    public function testParameterResolveByTypeWithVariadic(): void
    {
        $fn = static fn (\ArrayIterator ...$iterator) => $iterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with(\ArrayIterator::class)
            ->willReturn(new \ArrayIterator())
        ;
        $this->setContainer($mockContainer);
        $this->assertInstanceOf(
            \ArrayIterator::class,
            \call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters))[0]
        );
    }

    public function testParameterResolveByTypeNotFoundInContainerWithoutDefaultValue(): void
    {
        // SuperClass not registered in container.
        $fn = static fn (SuperClass $superClass) => $superClass;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with(SuperClass::class)
            ->willThrowException(new NotFoundException('Not found SuperClass'))
        ;
        $this->setContainer($mockContainer);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageMatches('/Unresolvable dependency.+SuperClass \$superClass.+Not found/');

        $this->resolveParameters([], $reflectionParameters);
    }

    public function testParameterResolveByTypeThrowWhenResolveDependency(): void
    {
        // SuperClass is registered in container, but fire throw when resolve in container.
        $fn = static fn (SuperClass $superClass) => $superClass;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with(SuperClass::class)
            ->willThrowException(new AutowireException('some error'))
        ;
        $this->setContainer($mockContainer);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Unresolvable dependency.+SuperClass \$superClass.+some error/');

        $this->resolveParameters([], $reflectionParameters);
    }
}
