<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Closure;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Tests\Traits\ParametersResolver\Fixtures\MoreSuperClass;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;

use function call_user_func_array;

/**
 * @covers \Kaspi\DiContainer\Attributes\ProxyClosure
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure
 * @covers \Kaspi\DiContainer\diProxyClosure
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 *
 * @internal
 */
class ParameterResolveUserDefinedArgumentByProxyClosureAttributeTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use DiContainerTrait;

    public function testResolveArgumentNoneVariadicAttribute(): void
    {
        /**
         * @param Closure(): MoreSuperClass $item
         */
        $fn = static fn (
            #[ProxyClosure(MoreSuperClass::class)]
            Closure $item
        ) => $item;
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
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig())
        ;

        $this->setContainer($mockContainer);

        $res = call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters, true));

        $this->assertInstanceOf(Closure::class, $res);
        $this->assertNotSame($res, call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters, true)));
        $this->assertInstanceOf(MoreSuperClass::class, $res());
    }

    public function testResolveArgumentNoneVariadicAttributeIsSingleton(): void
    {
        /**
         * @param Closure(): MoreSuperClass $item
         */
        $fn = static fn (
            #[ProxyClosure(MoreSuperClass::class, true)]
            Closure $item
        ) => $item;
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
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig())
        ;

        $this->setContainer($mockContainer);

        $res = call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters, true));

        $this->assertSame($res, call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters, true)));
    }

    public function testResolveArgumentVariadicByAttribute(): void
    {
        $fn = static fn (
            #[ProxyClosure(MoreSuperClass::class)]
            #[ProxyClosure(SuperClass::class)]
            Closure ...$item
        ) => $item;
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
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig())
        ;

        $this->setContainer($mockContainer);

        [$res1, $res2] = call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters, true));

        $this->assertInstanceOf(Closure::class, $res1);
        $this->assertInstanceOf(Closure::class, $res2);
        $this->assertInstanceOf(MoreSuperClass::class, $res1());
        $this->assertInstanceOf(SuperClass::class, $res2());
    }

    public function testResolveArgumentVariadicByAttributeIsSingleton(): void
    {
        $fn = static fn (
            #[ProxyClosure(MoreSuperClass::class, false)]
            #[ProxyClosure(SuperClass::class, true)]
            Closure ...$item
        ) => $item;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->method('has')
            ->with(self::logicalOr(
                MoreSuperClass::class,
                SuperClass::class
            ))
            ->willReturn(true)
        ;
        $mockContainer->method('get')
            ->with(self::logicalOr(
                MoreSuperClass::class,
                SuperClass::class
            ))
            ->willReturn(
                new MoreSuperClass(),
                new SuperClass()
            )
        ;
        $mockContainer->method('getConfig')
            ->willReturn(
                new DiContainerConfig()
            )
        ;

        $this->setContainer($mockContainer);

        [$res11, $res12] = call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters, true));
        [$res21, $res22] = call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters, true));

        $this->assertNotSame($res11, $res21);
        $this->assertSame($res12, $res22);
    }
}
