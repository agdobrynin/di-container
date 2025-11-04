<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Closure;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Traits\ArgumentResolverTrait;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Tests\Traits\ParametersResolver\Fixtures\MoreSuperClass;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;

use function call_user_func_array;
use function Kaspi\DiContainer\diProxyClosure;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure
 * @covers \Kaspi\DiContainer\diProxyClosure
 * @covers \Kaspi\DiContainer\Traits\ArgumentResolverTrait
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 *
 * @internal
 */
class ParameterResolveUserDefinedArgumentByProxyClosureTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use BindArgumentsTrait;
    use ArgumentResolverTrait;
    // 🧨 need for abstract method getContainer.
    use DiContainerTrait;

    public function testResolveArgumentNoneVariadicName(): void
    {
        $fn = static fn (Closure $item) => $item;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::once())
            ->method('has')
            ->with(MoreSuperClass::class)
            ->willReturn(true)
        ;
        $mockContainer->expects(self::once())
            ->method('get')
            ->with(MoreSuperClass::class)
            ->willReturn(new MoreSuperClass())
        ;

        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            item: diProxyClosure(MoreSuperClass::class),
        );

        $res = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters, false));

        $this->assertInstanceOf(Closure::class, $res);
        $this->assertInstanceOf(MoreSuperClass::class, $res());
    }

    public function testResolveArgumentNoneVariadicByNameIsSingleton(): void
    {
        $fn = static fn (Closure $item) => $item;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('has')
            ->with(MoreSuperClass::class)
            ->willReturn(true)
        ;
        $mockContainer->method('get')
            ->with(MoreSuperClass::class)
            ->willReturn(new MoreSuperClass())
        ;

        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            item: diProxyClosure(MoreSuperClass::class, true),
        );

        $res = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters, false));
        // $isSingleton will be ignored because argument bind through bindArguments()
        $this->assertNotSame($res, call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters, false)));
    }

    public function testResolveArgumentNoneVariadicByNameIsNoneSingleton(): void
    {
        $fn = static fn (Closure $item) => $item;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('has')
            ->with(MoreSuperClass::class)
            ->willReturn(true)
        ;
        $mockContainer->method('get')
            ->with(MoreSuperClass::class)
            ->willReturn(new MoreSuperClass())
        ;

        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            item: diProxyClosure(MoreSuperClass::class, false),
        );

        $res = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters, false));
        $this->assertNotSame($res, call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters, false)));
    }

    public function testResolveArgumentNoneVariadicByIndex(): void
    {
        $fn = static fn (Closure $item) => $item;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::once())
            ->method('has')
            ->with(MoreSuperClass::class)
            ->willReturn(true)
        ;
        $mockContainer->expects(self::once())
            ->method('get')
            ->with(MoreSuperClass::class)
            ->willReturn(new MoreSuperClass())
        ;

        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            diProxyClosure(MoreSuperClass::class),
        );

        $res = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters, false));

        $this->assertInstanceOf(Closure::class, $res);
        $this->assertInstanceOf(MoreSuperClass::class, $res());
    }

    public function testResolveArgumentVariadicByName(): void
    {
        $fn = static fn (Closure ...$item) => $item;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::exactly(2))
            ->method('has')
            ->willReturnMap([
                [MoreSuperClass::class, true],
                [SuperClass::class, true],
            ])
        ;
        $mockContainer->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [MoreSuperClass::class, new MoreSuperClass()],
                [SuperClass::class, new SuperClass()],
            ])
        ;

        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            item1: diProxyClosure(MoreSuperClass::class),
            item2: diProxyClosure(SuperClass::class),
        );

        ['item1' => $res1, 'item2' => $res2] = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters, false));

        $this->assertInstanceOf(Closure::class, $res1);
        $this->assertInstanceOf(Closure::class, $res2);
        $this->assertInstanceOf(MoreSuperClass::class, $res1());
        $this->assertInstanceOf(SuperClass::class, $res2());
    }

    public function testResolveArgumentVariadicByNameAndIsSingleton(): void
    {
        $fn = static fn (Closure ...$item) => $item;

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('has')
            ->willReturnMap([
                [MoreSuperClass::class, true],
                [SuperClass::class, true],
            ])
        ;
        $mockContainer->method('get')
            ->willReturnMap([
                [MoreSuperClass::class, new MoreSuperClass()],
                [SuperClass::class, new SuperClass()],
                [MoreSuperClass::class, new MoreSuperClass()],
            ])
        ;

        $this->setContainer($mockContainer);

        // 🚩 test data
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();
        $this->bindArguments(
            item: diProxyClosure(MoreSuperClass::class, false),
            item2: diProxyClosure(SuperClass::class, true),
            item3: diProxyClosure(MoreSuperClass::class, false),
        );

        ['item' => $res11, 'item2' => $res12, 'item3' => $res13] = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters, false));
        ['item' => $res21, 'item2' => $res22, 'item3' => $res23] = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters, false));

        $this->assertNotSame($res11, $res13);
        $this->assertNotSame($res21, $res23);
        $this->assertNotSame($res11, $res21);
        // because ignore isSingleton diProxyClosure(SuperClass::class, true), ignore in bindArguments()
        $this->assertNotSame($res12, $res22);
    }

    public function testResolveArgumentVariadicByIndex(): void
    {
        $fn = static fn (Closure ...$item) => $item;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::exactly(2))
            ->method('has')
            ->willReturnMap([
                [MoreSuperClass::class, true],
                [SuperClass::class, true],
            ])
        ;
        $mockContainer->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [SuperClass::class, new SuperClass()],
                [MoreSuperClass::class, new MoreSuperClass()],
            ])
        ;

        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            diProxyClosure(SuperClass::class),
            diProxyClosure(MoreSuperClass::class),
        );

        [$res1, $res2] = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters, false));

        $this->assertInstanceOf(Closure::class, $res1);
        $this->assertInstanceOf(Closure::class, $res2);
        $this->assertInstanceOf(SuperClass::class, $res1());
        $this->assertInstanceOf(MoreSuperClass::class, $res2());
    }
}
