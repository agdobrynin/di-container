<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 * @covers \Kaspi\DiContainer\Traits\PsrContainerTrait::getContainer
 *
 * @internal
 */
class ParameterResolveByTypeTest extends TestCase
{
    use ParametersResolverTrait;
    use PsrContainerTrait;

    public function testParameterResolveByType(): void
    {
        $fn = static fn (\ArrayIterator $array) => $array;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mock = $this->createMock(ContainerInterface::class);
        $mock->expects($this->once())
            ->method('get')->with(\ArrayIterator::class)
            ->willReturn(new \ArrayIterator())
        ;
        $this->container = $mock;

        $this->assertInstanceOf(\ArrayIterator::class, $this->resolveParameters()[0]);
    }

    public function testParameterResolveByName(): void
    {
        $fn = static fn ($myArrayIterator) => $myArrayIterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mock = $this->createMock(ContainerInterface::class);
        $mock->expects($this->once())
            ->method('get')->with('myArrayIterator')
            ->willReturn(new \ArrayIterator())
        ;
        $this->container = $mock;

        $this->assertInstanceOf(\ArrayIterator::class, $this->resolveParameters()[0]);
    }
}
