<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Traits\ParametersResolver\Fixtures\MoreSuperClass;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;

use function Kaspi\DiContainer\diProxyClosure;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure
 * @covers \Kaspi\DiContainer\diProxyClosure
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 *
 * @internal
 */
class ParameterResolveUserDefinedArgumentByProxyClosureTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use PsrContainerTrait;

    public function testResolveArgumentNoneVariadicName(): void
    {
        $fn = static fn (\Closure $item) => $item;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
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

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(\Closure::class, $res);
        $this->assertInstanceOf(MoreSuperClass::class, $res());
    }

    public function testResolveArgumentNoneVariadicByIndex(): void
    {
        $fn = static fn (\Closure $item) => $item;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
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

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(\Closure::class, $res);
        $this->assertInstanceOf(MoreSuperClass::class, $res());
    }

    public function testResolveArgumentVariadicByName(): void
    {
        $fn = static fn (\Closure ...$item) => $item;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
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

        [$res1, $res2] = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(\Closure::class, $res1);
        $this->assertInstanceOf(\Closure::class, $res2);
        $this->assertInstanceOf(MoreSuperClass::class, $res1());
        $this->assertInstanceOf(SuperClass::class, $res2());
    }

    public function testResolveArgumentVariadicByIndex(): void
    {
        $fn = static fn (\Closure ...$item) => $item;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
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

        [$res1, $res2] = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(\Closure::class, $res1);
        $this->assertInstanceOf(\Closure::class, $res2);
        $this->assertInstanceOf(SuperClass::class, $res1());
        $this->assertInstanceOf(MoreSuperClass::class, $res2());
    }
}
