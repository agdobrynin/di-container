<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Closure;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Tests\Traits\ParametersResolver\Fixtures\MoreSuperClass;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;

use function call_user_func_array;
use function Kaspi\DiContainer\diProxyClosure;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure
 * @covers \Kaspi\DiContainer\diProxyClosure
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 *
 * @internal
 */
class ParameterResolveUserDefinedArgumentByProxyClosureTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use BindArgumentsTrait;
    use ParametersResolverTrait;
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

        $res = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

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

        $res = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));
        $this->assertSame($res, call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters)));
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

        $res = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));
        $this->assertNotSame($res, call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters)));
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

        $res = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

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
            ->with(self::logicalOr(
                MoreSuperClass::class,
                SuperClass::class
            ))
            ->willReturn(
                true,
                true
            )
        ;
        $mockContainer->expects(self::exactly(2))
            ->method('get')
            ->with(self::logicalOr(
                MoreSuperClass::class,
                SuperClass::class
            ))
            ->willReturn(
                new MoreSuperClass(),
                new SuperClass()
            )
        ;

        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            item: [
                diProxyClosure(MoreSuperClass::class),
                diProxyClosure(SuperClass::class),
            ]
        );

        [$res1, $res2] = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

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
            ->with(self::logicalOr(
                MoreSuperClass::class,
                SuperClass::class
            ))
            ->willReturn(
                true
            )
        ;
        $mockContainer->method('get')
            ->with(self::logicalOr(
                MoreSuperClass::class,
                SuperClass::class
            ))
            ->willReturn(
                new MoreSuperClass(),
                new SuperClass(),
                new MoreSuperClass(),
            )
        ;

        $this->setContainer($mockContainer);

        // 🚩 test data
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();
        $this->bindArguments(
            item: [
                diProxyClosure(MoreSuperClass::class, false), // ➖
                diProxyClosure(SuperClass::class, true), // ➕
                diProxyClosure(MoreSuperClass::class, false), // ➖
            ]
        );

        [$res11, $res12, $res13] = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));
        [$res21, $res22, $res23] = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertNotSame($res11, $res13);
        $this->assertNotSame($res21, $res23);
        $this->assertNotSame($res11, $res21);
        $this->assertSame($res12, $res22); // because diProxyClosure(SuperClass::class, true)
    }

    public function testResolveArgumentVariadicByIndex(): void
    {
        $fn = static fn (Closure ...$item) => $item;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::exactly(2))
            ->method('has')
            ->with(self::logicalOr(
                MoreSuperClass::class,
                SuperClass::class
            ))
            ->willReturn(
                true,
                true
            )
        ;
        $mockContainer->expects(self::exactly(2))
            ->method('get')
            ->with(self::logicalOr(
                MoreSuperClass::class,
                SuperClass::class
            ))
            ->willReturn(
                new SuperClass(),
                new MoreSuperClass(),
            )
        ;

        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            diProxyClosure(SuperClass::class),
            diProxyClosure(MoreSuperClass::class),
        );

        [$res1, $res2] = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertInstanceOf(Closure::class, $res1);
        $this->assertInstanceOf(Closure::class, $res2);
        $this->assertInstanceOf(SuperClass::class, $res1());
        $this->assertInstanceOf(MoreSuperClass::class, $res2());
    }
}
