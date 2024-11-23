<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;

/**
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::setContainer
 * @covers \Kaspi\DiContainer\Traits\PsrContainerTrait::getContainer
 *
 * @internal
 */
class ParameterResolveByTypeOrArgumentNameTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use PsrContainerTrait;

    public function testParameterResolveByType(): void
    {
        $fn = static fn (\ArrayIterator $array) => $array;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with(\ArrayIterator::class)
            ->willReturn(new \ArrayIterator())
        ;
        $this->setContainer($mockContainer);

        $this->assertInstanceOf(
            \ArrayIterator::class,
            \call_user_func_array($fn, $this->resolveParameters())
        );
    }

    public function testParameterResolveByName(): void
    {
        $fn = static fn ($myArrayIterator) => $myArrayIterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with('myArrayIterator')
            ->willReturn(new \ArrayIterator())
        ;
        $this->setContainer($mockContainer);

        $this->assertInstanceOf(
            \ArrayIterator::class,
            \call_user_func_array($fn, $this->resolveParameters())
        );
    }

    public function testParameterResolveByNameVariadicParameterString(): void
    {
        $fn = static fn (SuperClass $superClass, string ...$word) => [$superClass, $word];
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer
            ->expects($this->atMost(2))
            ->method('get')
            ->with($this->logicalOr(
                SuperClass::class,
                'word'
            ))
            ->willReturn(
                new SuperClass(),
                ['one', 'two', 'three']
            )
        ;

        $this->setContainer($mockContainer);

        $params = $this->resolveParameters();

        $this->assertCount(4, $params);
        $this->assertInstanceOf(SuperClass::class, $params[0]);
        $this->assertEquals('one', $params[1]);
        $this->assertEquals('two', $params[2]);
        $this->assertEquals('three', $params[3]);

        $this->assertInstanceOf(SuperClass::class, \call_user_func_array($fn, $params)[0]);
        $this->assertEquals(['one', 'two', 'three'], \call_user_func_array($fn, $params)[1]);
    }

    public function testParameterResolveByNameVariadicParameterArray(): void
    {
        $fn = static fn (array ...$phrase) => $phrase;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer
            ->expects($this->once())
            ->method('get')
            ->with('phrase')
            ->willReturn(
                [
                    ['one', 'two', 'three'],
                    ['four', 'five', 'six'],
                ]
            )
        ;

        $this->setContainer($mockContainer);

        $params = $this->resolveParameters();

        $this->assertCount(2, $params);
        $this->assertEquals(
            [['one', 'two', 'three'], ['four', 'five', 'six']],
            \call_user_func_array($fn, $params)
        );
    }

    public function testParameterResolveByTypeNotFoundAndSetDefaultValue(): void
    {
        $fn = static fn (?SomeClass $someClass = null) => $someClass;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with(SomeClass::class)
            ->willThrowException(new NotFoundException())
        ;
        $this->setContainer($mockContainer);

        $this->assertNull(\call_user_func_array($fn, $this->resolveParameters()));
    }

    public function testParameterResolveByTypeWithVariadic(): void
    {
        $fn = static fn (\ArrayIterator ...$iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with(\ArrayIterator::class)
            ->willReturn(new \ArrayIterator())
        ;
        $this->setContainer($mockContainer);
        $this->assertInstanceOf(
            \ArrayIterator::class,
            \call_user_func_array($fn, $this->resolveParameters())[0]
        );
    }

    public function testParameterResolveByTypeNotFoundInContainerWithoutDefaultValue(): void
    {
        // SuperClass not registered in container.
        $fn = static fn (SuperClass $superClass) => $superClass;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with(SuperClass::class)
            ->willThrowException(new NotFoundException())
        ;
        $this->setContainer($mockContainer);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageMatches('/Unresolvable dependency.+SuperClass \$superClass/');

        $this->resolveParameters();
    }
}
