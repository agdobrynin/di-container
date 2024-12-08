<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Traits\ParametersResolver\Fixtures\MoreSuperClass;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;

use function Kaspi\DiContainer\diAsClosure;

/**
 * @covers \Kaspi\DiContainer\diAsClosure
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionClosure
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 *
 * @internal
 */
class ParameterResolveByUserDefinedArgumentByAsClosureTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use PsrContainerTrait;

    public function testResolveArgumentNoneVariadicAsClosureByName(): void
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
            item: diAsClosure(MoreSuperClass::class),
        );

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(\Closure::class, $res);
        $this->assertInstanceOf(MoreSuperClass::class, $res());
    }

    public function testResolveArgumentNoneVariadicAsClosureByIndex(): void
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
            diAsClosure(MoreSuperClass::class),
        );

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(\Closure::class, $res);
        $this->assertInstanceOf(MoreSuperClass::class, $res());
    }

    public function testResolveArgumentVariadicAsClosureByName(): void
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
                diAsClosure(MoreSuperClass::class),
                diAsClosure(SuperClass::class),
            ]
        );

        [$res1, $res2] = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(\Closure::class, $res1);
        $this->assertInstanceOf(\Closure::class, $res2);
        $this->assertInstanceOf(MoreSuperClass::class, $res1());
        $this->assertInstanceOf(SuperClass::class, $res2());
    }

    public function testResolveArgumentVariadicAsClosureByIndex(): void
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
            diAsClosure(SuperClass::class),
            diAsClosure(MoreSuperClass::class),
        );

        [$res1, $res2] = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(\Closure::class, $res1);
        $this->assertInstanceOf(\Closure::class, $res2);
        $this->assertInstanceOf(SuperClass::class, $res1());
        $this->assertInstanceOf(MoreSuperClass::class, $res2());
    }
}
