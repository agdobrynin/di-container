<?php

declare(strict_types=1);

namespace Tests\Traits\BindArguments;

use ArrayIterator;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use stdClass;
use Tests\Traits\BindArguments\Fixtures\Bar;
use Tests\Traits\BindArguments\Fixtures\Baz;
use Tests\Traits\BindArguments\Fixtures\Foo;

use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\functionNameByParameter
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::getParameterType
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 *
 * @internal
 */
class GetParametersTest extends TestCase
{
    use BindArgumentsTrait;
    use DiContainerTrait;

    public function testGetWithoutParameters(): void
    {
        $fn = static fn () => '';

        $this->bindArguments('one', 'two', diGet('services.logger_file'));
        $params = (new ReflectionFunction($fn))->getParameters();

        self::assertEquals(
            ['one', 'two', diGet('services.logger_file')],
            $this->getParameters($params, false)
        );
    }

    public function testResolveByParameterTypeByParameterNameByDefaultValue(): void
    {
        $fn = static fn (ArrayIterator $iterator, $serviceLook, $dto = new stdClass()): array => [$iterator, $serviceLook, $dto];

        $params = (new ReflectionFunction($fn))->getParameters();

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('has')
            ->willReturnMap([
                ['ArrayIterator', true],
                ['serviceLook', true],
                ['dto', false],
            ])
        ;

        $this->setContainer($containerMock);

        self::assertEquals(
            [diGet('ArrayIterator'), diGet('serviceLook'), new stdClass()],
            $this->getParameters($params, false)
        );
    }

    public function testGetParameterTypeWithDefaultValue(): void
    {
        $fn = static fn (Bar|Foo $fooBar = new Baz()): Bar|Foo => $fooBar;

        $params = (new ReflectionFunction($fn))->getParameters();

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('has')
            ->willReturnMap([
                [Foo::class, true],
                [Bar::class, true],
            ])
        ;

        $this->setContainer($containerMock);

        self::assertInstanceOf(
            Baz::class,
            $this->getParameters($params, false)[0]
        );
    }

    public function testGetParameterIntersectionTypeWithDefaultValue(): void
    {
        $fn = static fn (Bar&Foo $fooBar = new Baz()): Bar&Foo => $fooBar;

        $params = (new ReflectionFunction($fn))->getParameters();

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('has')
            ->willReturnMap([
                ['fooBar', false],
            ])
        ;

        $this->setContainer($containerMock);

        self::assertInstanceOf(
            Baz::class,
            $this->getParameters($params, false)[0]
        );
    }

    public function testExceptionGetParameterIntersectionType(): void
    {
        $fn = static fn (Bar&Foo $fooBar): Bar&Foo => $fooBar;

        $params = (new ReflectionFunction($fn))->getParameters();

        $this->setContainer($this->createMock(DiContainerInterface::class));

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/^Cannot automatically resolve dependency.+Bar&.+Foo \$fooBar/');

        $this->getParameters($params, false);
    }

    public function testExceptionGetParameterUnresolvedType(): void
    {
        $fn = static fn (Foo $foo): Foo => $foo;

        $params = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = self::createMock(DiContainerInterface::class);
        $mockContainer->method('has')
            ->willReturnMap([
                [Foo::class, false],
                ['foo', false],
            ])
        ;

        $this->setContainer($mockContainer);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/^Unresolvable dependency.+ \$foo /');

        $this->getParameters($params, false);
    }
}
