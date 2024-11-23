<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

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
    use ParametersResolverTrait;
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

        $this->assertInstanceOf(\ArrayIterator::class, $this->resolveParameters()[0]);
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

        $this->assertInstanceOf(\ArrayIterator::class, $this->resolveParameters()[0]);
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

        $this->assertNull($this->resolveParameters()[0]);
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

        $this->assertInstanceOf(\ArrayIterator::class, $this->resolveParameters()[0]);
    }
}
