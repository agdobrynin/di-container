<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use ArrayIterator;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use stdClass;
use Tests\Traits\ParametersResolver\Fixtures\MoreSuperClass;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;
use Tests\Traits\ParametersResolver\Fixtures\SuperDiFactory;
use Tests\Traits\ParametersResolver\Fixtures\SuperInterface;

use function array_keys;
use function array_map;
use function call_user_func_array;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class ParameterResolveByInjectAttributeTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use DiContainerTrait;

    public function testParameterResolveTypedArgumentByInjectAttributeWithEmptyIdentifier(): void
    {
        $fn = static fn (
            #[Inject]
            ArrayIterator $iterator
        ) => $iterator;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with(ArrayIterator::class)
            ->willReturn(new ArrayIterator(['✔', '❤']))
        ;
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $this->setContainer($mockContainer);

        $params = $this->resolveParameters([], $reflectionParameters, true);
        $this->assertEquals(
            ['✔', '❤'],
            call_user_func_array($fn, $params)->getArrayCopy()
        );
    }

    public function testParameterResolveTypedArgumentByInjectAttributeThrowManyInjectNonVariadic(): void
    {
        $fn = static fn (
            #[Inject('a')]
            #[Inject('b')]
            SuperClass $iterator
        ) => $iterator;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');

        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $this->setContainer($mockContainer);

        $this->expectException(AutowireAttributeException::class);
        $this->expectExceptionMessage('once per non-variadic parameter');

        $this->resolveParameters([], $reflectionParameters, true);
    }

    public function testParameterResolveTypedVariadicArgumentByTowInjectAttributeWithId(): void
    {
        $fn = static fn (
            #[Inject('services.one')]
            #[Inject('services.tow')]
            SuperInterface ...$super
        ) => $super;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->atLeast(2))
            ->method('get')
            ->willReturnMap([
                ['services.one', new SuperClass()],
                ['services.tow', new MoreSuperClass()],
            ])
        ;
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $this->setContainer($mockContainer);

        $res = call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters, true));

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
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')
            ->with(SuperDiFactory::class)
            ->willReturn(new SuperClass())
        ;
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $this->setContainer($mockContainer);

        $res = call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters, true));

        $this->assertIsArray($res);
        $this->assertInstanceOf(SuperInterface::class, $res[0]);
    }

    public function testParameterResolveByArgumentName(): void
    {
        $fn = static fn (
            #[Inject]
            object|string $parameter
        ) => $parameter;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')
            ->with('parameter')
            ->willReturn(new stdClass())
        ;
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $this->setContainer($mockContainer);

        $res = call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters, true));

        $this->assertIsObject($res);
    }

    public function testParameterResolveByArgumentNameNotFound(): void
    {
        $fn = static fn (
            #[Inject]
            object|string $parameter
        ) => $parameter;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')
            ->with('parameter')
            ->willThrowException(new NotFoundException('Not found'))
        ;
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $this->setContainer($mockContainer);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageMatches('/Unresolvable dependency.+object\|string \$parameter.+Not found/');

        $this->resolveParameters([], $reflectionParameters, true);
    }

    public function testParameterResolveByArgumentNameNotFoundWithDefaultValue(): void
    {
        $fn = static fn (
            #[Inject]
            object|string $parameter = 'welcome!'
        ) => $parameter;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')
            ->with('parameter')
            ->willThrowException(new NotFoundException('Not found'))
        ;
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $this->setContainer($mockContainer);

        $res = call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters, true));

        $this->assertEquals('welcome!', $res);
    }

    public function testParameterResolveByArgumentNameNonVariadicTypeArray(): void
    {
        $fn = static fn (
            #[Inject('names')]
            array $name
        ): array => $name;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')
            ->with('names')
            ->willReturn(['Ivan', 'Piter'])
        ;
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $this->setContainer($mockContainer);

        $res = call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters, true));

        $this->assertEquals(['Ivan', 'Piter'], $res);
    }

    public function testParameterResolveByArgumentNameVariadicTypeArray(): void
    {
        $fn = static fn (
            #[Inject('names')]
            array ...$name
        ): array => array_map(
            static fn (array $name, int $index) => $index > 0 ? array_map('strtoupper', $name) : array_map('strtolower', $name),
            $name,
            array_keys($name)
        );
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')
            ->with('names')
            ->willReturn(['IvaN', 'PiteR'])
        ;
        $mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: true))
        ;

        $this->setContainer($mockContainer);

        $res = call_user_func_array($fn, $this->resolveParameters([], $reflectionParameters, true));

        $this->assertCount(1, $res);

        $this->assertEquals(['ivan', 'piter'], $res[0]);
    }
}
