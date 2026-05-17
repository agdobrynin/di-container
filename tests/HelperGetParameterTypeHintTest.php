<?php

declare(strict_types=1);

namespace Tests;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

/**
 * @internal
 */
#[CoversClass(Helper::class)]
class HelperGetParameterTypeHintTest extends TestCase
{
    public function testBuiltinClass(): void
    {
        $param = new ReflectionParameter(
            static fn (ArrayIterator $a) => true,
            0
        );

        $res = Helper::getParameterTypeHint($param, $this->createMock(DiContainerInterface::class));

        self::assertEquals('ArrayIterator', $res);
    }

    public function testUserClass(): void
    {
        $param = new ReflectionParameter(
            static fn (Foo $a) => true,
            0
        );

        $res = Helper::getParameterTypeHint($param, $this->createMock(DiContainerInterface::class));

        self::assertEquals('Tests\Foo', $res);
    }

    public function testIntersectInterface(): void
    {
        $this->expectException(AutowireParameterTypeException::class);
        $this->expectExceptionMessage('Please specify the Parameter #0 [ <required> ArrayAccess&Countable $a ]');

        $param = new ReflectionParameter(
            static fn (ArrayAccess&Countable $a) => true,
            0
        );

        Helper::getParameterTypeHint($param, $this->createMock(DiContainerInterface::class));
    }

    public function testUnionBazFalseFooTrue(): void
    {
        $param = new ReflectionParameter(
            static fn (Baz|Foo|string $a) => true,
            0
        );

        $container = $this->createMock(DiContainerInterface::class);
        $container->expects(self::exactly(2))
            ->method('has')
            ->willReturnMap([
                ['Tests\Baz', false],
                ['Tests\Foo', true],
            ])
        ;

        $res = Helper::getParameterTypeHint($param, $container);

        self::assertEquals('Tests\Foo', $res);
    }

    public function testUnionBarTrueFooTrue(): void
    {
        $this->expectException(AutowireParameterTypeException::class);
        $this->expectExceptionMessage('Please specify the Parameter #0 [ <required> Tests\Bar|Tests\Foo $a ]');

        $param = new ReflectionParameter(
            static fn (Bar|Foo $a) => true,
            0
        );

        $container = $this->createMock(DiContainerInterface::class);
        $container->expects(self::exactly(2))
            ->method('has')
            ->willReturnMap([
                ['Tests\Bar', true],
                ['Tests\Foo', true],
            ])
        ;

        Helper::getParameterTypeHint($param, $container);

        self::assertEquals('Tests\Foo', $res);
    }
}

final class Foo {}
final class Bar {}
