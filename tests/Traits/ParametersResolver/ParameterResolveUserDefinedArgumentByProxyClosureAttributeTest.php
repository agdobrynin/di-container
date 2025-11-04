<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Closure;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Traits\ArgumentResolverTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
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
 * @covers \Kaspi\DiContainer\Traits\ArgumentResolverTrait
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 *
 * @internal
 */
class ParameterResolveUserDefinedArgumentByProxyClosureAttributeTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ArgumentResolverTrait;
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
}
