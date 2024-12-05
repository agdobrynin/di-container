<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Traits\ParametersResolver\Fixtures\ClassWithDependency;
use Tests\Traits\ParametersResolver\Fixtures\MoreSuperClass;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;
use Tests\Traits\ParametersResolver\Fixtures\SuperDiFactory;
use Tests\Traits\ParametersResolver\Fixtures\SuperInterface;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class ParameterResolveByUserDefinedArgumentByDiAutowireDefinitionInterfaceTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use PsrContainerTrait;

    public function testResolveByAutowireDefinitionNonVariadicByName(): void
    {
        $fn = static fn (ClassWithDependency $class) => $class;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            class: diAutowire(ClassWithDependency::class)
                ->bindArguments(dependency: 'aaaa')
        );

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(ClassWithDependency::class, $res);
    }

    public function testResolveByAutowireDefinitionNonVariadicByIndex(): void
    {
        $fn = static fn (ClassWithDependency $class) => $class;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            diAutowire(ClassWithDependency::class)
                ->bindArguments(dependency: 'aaaa')
        );

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(ClassWithDependency::class, $res);
    }

    public function testResolveByAutowireDefinitionVariadicByArrayByName(): void
    {
        $fn = static fn (SuperInterface ...$item) => $item;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects(self::exactly(2))
            ->method('get')
            ->with(MoreSuperClass::class)
            ->willReturn(new MoreSuperClass())
        ;

        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            item: [
                diAutowire(SuperClass::class),
                diAutowire(SuperDiFactory::class),
            ]
        );

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertCount(2, $res);
        $this->assertInstanceOf(SuperClass::class, $res[0]);
        $this->assertInstanceOf(MoreSuperClass::class, $res[1]);

        // none-singleton
        $this->assertNotSame(
            $res[0],
            \call_user_func_array($fn, $this->resolveParameters())[0]
        );
    }

    public function testResolveByAutowireDefinitionVariadicByArrayByIndex(): void
    {
        $fn = static fn (SuperInterface ...$item) => $item;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            item: [
                diAutowire(SuperClass::class),
                diAutowire(MoreSuperClass::class),
            ]
        );

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(SuperClass::class, $res[0]);
        $this->assertInstanceOf(MoreSuperClass::class, $res[1]);
    }

    public function testResolveByAutowireDefinitionVariadicByArrayAndSingletonByName(): void
    {
        $fn = static fn (SuperInterface ...$item) => $item;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            item: [
                diAutowire(SuperClass::class, true),
                diAutowire(MoreSuperClass::class, true),
            ]
        );

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertCount(2, $res);
        $this->assertInstanceOf(SuperClass::class, $res[0]);
        $this->assertInstanceOf(MoreSuperClass::class, $res[1]);

        // is singleton
        $this->assertSame(
            $res[0],
            \call_user_func_array($fn, $this->resolveParameters())[0]
        );
    }

    public function testResolveByAutowireDefinitionVariadicByDiGetAkaTagByName(): void
    {
        $fn = static fn (SuperInterface ...$item) => $item;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects(self::exactly(2))
            ->method('get')
            ->with(self::logicalOr(
                'services.super.one',
                'services.super.two'
            ))
            ->willReturn(
                new MoreSuperClass(),
                new SuperClass(),
            )
        ;
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            item: [
                diGet('services.super.one'),
                diGet('services.super.two'),
            ]
        );

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertCount(2, $res);
        $this->assertInstanceOf(MoreSuperClass::class, $res[0]);
        $this->assertInstanceOf(SuperClass::class, $res[1]);
    }

    public function testResolveByAutowireDefinitionVariadicByDiGetAkaTagByIndex(): void
    {
        $fn = static fn (SuperInterface ...$item) => $item;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects(self::exactly(2))
            ->method('get')
            ->with(self::logicalOr(
                'services.super.one',
                'services.super.two',
            ))
            ->willReturn(
                new MoreSuperClass(),
                new SuperClass(),
            )
        ;
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            diGet('services.super.one'),
            diGet('services.super.two')
        );

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertCount(2, $res);
        $this->assertInstanceOf(MoreSuperClass::class, $res[0]);
        $this->assertInstanceOf(SuperClass::class, $res[1]);
    }
}
