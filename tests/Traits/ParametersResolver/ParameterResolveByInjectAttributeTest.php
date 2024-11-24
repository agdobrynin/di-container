<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Traits\ParametersResolver\Fixtures\MoreSuperClass;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;
use Tests\Traits\ParametersResolver\Fixtures\SuperDiFactory;
use Tests\Traits\ParametersResolver\Fixtures\SuperInterface;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject::getIdentifier
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::getInjectAttribute
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait::getParameterTypeByReflection
 * @covers \Kaspi\DiContainer\Traits\PsrContainerTrait::getContainer
 * @covers \Kaspi\DiContainer\Traits\PsrContainerTrait::setContainer
 *
 * @internal
 */
class ParameterResolveByInjectAttributeTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use PsrContainerTrait;

    public function testParameterResolveTypedArgumentByInjectAttributeWithEmptyIdentifier(): void
    {
        $fn = static fn (
            #[Inject]
            \ArrayIterator $iterator
        ) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with(\ArrayIterator::class)
            ->willReturn(new \ArrayIterator(['✔', '❤']))
        ;
        $this->setContainer($mockContainer);

        $params = $this->resolveParameters(useAttribute: true);
        $this->assertEquals(
            ['✔', '❤'],
            \call_user_func_array($fn, $params)->getArrayCopy()
        );
    }

    public function testParameterResolveTypedArgumentByInjectAttributeThrowManyInjectNonVariadic(): void
    {
        $fn = static fn (
            #[Inject('a')]
            #[Inject('b')]
            SuperClass $iterator
        ) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        $this->expectException(AutowiredAttributeException::class);
        $this->expectExceptionMessage('once per non-variadic parameter');

        $this->resolveParameters(useAttribute: true);
    }

    public function testParameterResolveTypedVariadicArgumentByTowInjectAttributeWithId(): void
    {
        $fn = static fn (
            #[Inject('services.one')]
            #[Inject('services.tow')]
            SuperInterface ...$super
        ) => $super;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->atLeast(2))
            ->method('get')
            ->with($this->logicalOr(
                'services.one',
                'services.tow',
            ))
            ->willReturn(
                new SuperClass(),
                new MoreSuperClass(),
            )
        ;
        $this->setContainer($mockContainer);

        $res = \call_user_func_array($fn, $this->resolveParameters(useAttribute: true));

        $this->assertIsArray($res);
        $this->assertInstanceOf(SuperInterface::class, $res[0]);
        $this->assertInstanceOf(SuperInterface::class, $res[1]);
    }

    public function testParameterResolveTypedVariadicArgumentByOneInjectAttributeWithIdAkaDiFactory(): void
    {
        $fn = static fn (
            #[Inject(SuperDiFactory::class)]
            SuperInterface ...$super
        ) => $super;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')
            ->with(SuperDiFactory::class)
            ->willReturn([
                new SuperClass(),
                new MoreSuperClass(),
            ])
        ;
        $this->setContainer($mockContainer);

        $res = \call_user_func_array($fn, $this->resolveParameters(useAttribute: true));

        $this->assertIsArray($res);
        $this->assertInstanceOf(SuperInterface::class, $res[0]);
        $this->assertInstanceOf(SuperInterface::class, $res[1]);
    }
}
