<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Exception\AutowiredAttributeException;
use Kaspi\DiContainer\Exception\NotFoundException;
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
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait::isUseAttribute
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait::setUseAttribute
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
        $this->setUseAttribute(true);

        $params = $this->resolveParameters();
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
        $this->setUseAttribute(true);

        $this->expectException(AutowiredAttributeException::class);
        $this->expectExceptionMessage('once per non-variadic parameter');

        $this->resolveParameters();
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
        $this->setUseAttribute(true);

        $res = \call_user_func_array($fn, $this->resolveParameters());

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
        $this->setUseAttribute(true);

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertIsArray($res);
        $this->assertInstanceOf(SuperInterface::class, $res[0]);
        $this->assertInstanceOf(SuperInterface::class, $res[1]);
    }

    public function testParameterResolveByArgumentName(): void
    {
        $fn = static fn (
            #[Inject]
            object|string $parameter
        ) => $parameter;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')
            ->with('parameter')
            ->willReturn(new \stdClass())
        ;
        $this->setContainer($mockContainer);
        $this->setUseAttribute(true);

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertIsObject($res);
    }

    public function testParameterResolveByArgumentNameNotFound(): void
    {
        $fn = static fn (
            #[Inject]
            object|string $parameter
        ) => $parameter;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')
            ->with('parameter')
            ->willThrowException(new NotFoundException('Not found'))
        ;
        $this->setContainer($mockContainer);
        $this->setUseAttribute(true);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageMatches('/Unresolvable dependency.+object\|string \$parameter.+Not found/');

        $this->resolveParameters();
    }

    public function testParameterResolveByArgumentNameNotFoundWithDefaultValue(): void
    {
        $fn = static fn (
            #[Inject]
            object|string $parameter = 'welcome!'
        ) => $parameter;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')
            ->with('parameter')
            ->willThrowException(new NotFoundException('Not found'))
        ;
        $this->setContainer($mockContainer);
        $this->setUseAttribute(true);

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertEquals('welcome!', $res);
    }

    public function testParameterResolveByArgumentNameNonVariadicTypeArray(): void
    {
        $fn = static fn (
            #[Inject('names')]
            array $name
        ): array => $name;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')
            ->with('names')
            ->willReturn(['Ivan', 'Piter'])
        ;
        $this->setContainer($mockContainer);
        $this->setUseAttribute(true);

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertEquals(['Ivan', 'Piter'], $res);
    }

    public function testParameterResolveByArgumentNameVariadicTypeArray(): void
    {
        $fn = static fn (
            #[Inject('names')]
            array ...$name
        ): array => \array_map(
            static fn (array $name, int $index) => $index > 0 ? \array_map('strtoupper', $name) : \array_map('strtolower', $name),
            $name,
            \array_keys($name)
        );
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')
            ->with('names')
            ->willReturn([['IvaN', 'PiteR'], ['vasIliy', 'niKOlay']])
        ;
        $this->setContainer($mockContainer);
        $this->setUseAttribute(true);

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertCount(2, $res);

        $this->assertEquals(['ivan', 'piter'], $res[0]);
        $this->assertEquals(['VASILIY', 'NIKOLAY'], $res[1]);
    }
}
