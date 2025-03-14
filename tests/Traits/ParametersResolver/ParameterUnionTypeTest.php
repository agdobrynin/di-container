<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Tests\Traits\ParametersResolver\Fixtures\One;
use Tests\Traits\ParametersResolver\Fixtures\Two;

use function call_user_func_array;
use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class ParameterUnionTypeTest extends TestCase
{
    use ParametersResolverTrait;
    use DiContainerTrait;

    private ?object $mockContainer = null;

    public function setUp(): void
    {
        $this->mockContainer = $this->createMock(DiContainerInterface::class);
        $this->mockContainer->method('has')
            ->willReturn(true)
        ;
    }

    public function tearDown(): void
    {
        $this->mockContainer = null;
    }

    public function testUnionTypeByPhpDefinitionFail(): void
    {
        $fn = static fn (One|Two $type): string => '';

        $this->setContainer($this->mockContainer);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot automatically resolve dependency.+"\$type".+One.+Two/');

        $this->resolveParameters([], (new ReflectionFunction($fn))->getParameters());
    }

    public function testUnionTypeByPhpDefinitionSuccess(): void
    {
        $fn = static fn (One|Two $type): One|Two => $type;

        $this->mockContainer->method('get')
            ->with(Two::class)
            ->willReturn(new Two())
        ;

        $this->setContainer($this->mockContainer);

        $res = $this->resolveParameters(
            [diGet(Two::class)],
            (new ReflectionFunction($fn))->getParameters()
        );

        $this->assertInstanceOf(Two::class, call_user_func_array($fn, $res));
    }

    public function testUnionTypeByPhpAttributeFail(): void
    {
        $fn = static fn (
            #[Inject]
            One|Two $type
        ): string => '';

        $this->mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig())
        ;

        $this->setContainer($this->mockContainer);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot automatically resolve dependency.+"\$type".+One.+Two/');

        $this->resolveParameters([], (new ReflectionFunction($fn))->getParameters());
    }

    public function testUnionTypeByPhpAttributeSuccess(): void
    {
        $fn = static fn (
            #[Inject(Two::class)]
            One|Two $type
        ): One|Two => $type;

        $this->mockContainer->method('getConfig')
            ->willReturn(new DiContainerConfig())
        ;
        $this->mockContainer->method('get')
            ->with(Two::class)
            ->willReturn(new Two())
        ;

        $this->setContainer($this->mockContainer);

        $res = $this->resolveParameters([], (new ReflectionFunction($fn))->getParameters());

        $this->assertInstanceOf(Two::class, call_user_func_array($fn, $res));
    }
}
