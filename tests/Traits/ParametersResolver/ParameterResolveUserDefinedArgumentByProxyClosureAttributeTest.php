<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Traits\ParametersResolver\Fixtures\MoreSuperClass;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;

/**
 * @covers \Kaspi\DiContainer\Attributes\ProxyClosure
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure
 * @covers \Kaspi\DiContainer\diProxyClosure
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class ParameterResolveUserDefinedArgumentByProxyClosureAttributeTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use PsrContainerTrait;

    public function testResolveArgumentNoneVariadicAttribute(): void
    {
        /**
         * @param \Closure(): MoreSuperClass $item
         */
        $fn = static fn (
            #[ProxyClosure(MoreSuperClass::class)]
            \Closure $item
        ) => $item;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $this->setUseAttribute(true);

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

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(\Closure::class, $res);
        $this->assertInstanceOf(MoreSuperClass::class, $res());
    }

    public function testResolveArgumentVariadicByAttribute(): void
    {
        $fn = static fn (
            #[ProxyClosure(MoreSuperClass::class)]
            #[ProxyClosure(SuperClass::class)]
            \Closure ...$item
        ) => $item;
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
        $this->setUseAttribute(true);

        [$res1, $res2] = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(\Closure::class, $res1);
        $this->assertInstanceOf(\Closure::class, $res2);
        $this->assertInstanceOf(MoreSuperClass::class, $res1());
        $this->assertInstanceOf(SuperClass::class, $res2());
    }
}
