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

    public function setUp(): void
    {
        $this->bindArguments();
    }

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
        $fn = static fn (ArrayIterator $iterator, $dto = new stdClass()): array => [$iterator, $dto];

        $params = (new ReflectionFunction($fn))->getParameters();

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('has')
            ->with('ArrayIterator')
            ->willReturn(true)
        ;
        $containerMock->method('get')
            ->with('ArrayIterator')
            ->willReturn(new ArrayIterator())
        ;

        $this->setContainer($containerMock);

        $args = $this->getParameters($params, false);

        self::assertEquals([diGet('ArrayIterator')], $args);
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

        self::assertEmpty($this->getParameters($params, false));
    }

    public function testGetParameterTypeOnce(): void
    {
        $fn = static fn (Bar|Foo $fooBar = new Baz()): Bar|Foo => $fooBar;

        $params = (new ReflectionFunction($fn))->getParameters();

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('has')
            ->willReturnMap([
                [Bar::class, true],
                [Foo::class, false],
            ])
        ;

        $this->setContainer($containerMock);

        self::assertEquals(
            [diGet(Bar::class)],
            $this->getParameters($params, false)
        );
    }

    public function testGetParameterIntersectionTypeWithDefaultValue(): void
    {
        $fn = static fn (Bar&Foo $fooBar = new Baz()): Bar&Foo => $fooBar;

        $params = (new ReflectionFunction($fn))->getParameters();

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('has')
            ->willReturnMap([
                [Bar::class, true],
                [Foo::class, true],
            ])
        ;

        $this->setContainer($containerMock);

        self::assertEmpty($this->getParameters($params, false));
    }

    public function testExceptionGetParameterIntersectionType(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/^Cannot automatically resolve dependency.+Bar&.+Foo \$fooBar/');

        $fn = static fn (Bar&Foo $fooBar): Bar&Foo => $fooBar;
        $params = (new ReflectionFunction($fn))->getParameters();

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('has')
            ->willReturnMap([
                [Bar::class, true],
                [Foo::class, true],
            ])
        ;

        $this->setContainer($containerMock);

        $this->getParameters($params, false);
    }

    public function testVariadicParameterWithoutArgument(): void
    {
        $fn = static fn (Bar $bar, Foo ...$foo): array => [$bar, $foo];
        $params = (new ReflectionFunction($fn))->getParameters();

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('has')
            ->willReturnMap([
                [Bar::class, true],
                [Foo::class, true],
            ])
        ;

        $this->setContainer($containerMock);

        self::assertEquals(
            [diGet(Bar::class)],
            $this->getParameters($params, false)
        );
    }

    public function testVariadicParameterBindArgument(): void
    {
        $fn = static fn (Bar $bar, Foo ...$foo): array => [$bar, $foo];
        $params = (new ReflectionFunction($fn))->getParameters();

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('has')
            ->willReturnMap([
                [Bar::class, true],
                [Foo::class, true],
            ])
        ;

        $this->setContainer($containerMock);

        $this->bindArguments(
            foo: [
                diGet(Foo::class),
                diGet(Baz::class),
            ]
        );

        self::assertEquals(
            [
                diGet(Bar::class),
                diGet(Foo::class),
                diGet(Baz::class),
            ],
            $this->getParameters($params, false)
        );
    }

    public function testDefaultParameter(): void
    {
        $fn = static fn (Foo $foo, Bar|Foo $bar = new Baz()): array => [$foo, $bar];
        $params = (new ReflectionFunction($fn))->getParameters();

        $containerMock = $this->createMock(DiContainerInterface::class);
        $containerMock->method('has')
            ->willReturnMap([
                [Foo::class, true],
                [Bar::class, true],
            ])
        ;

        $this->setContainer($containerMock);

        self::assertEquals(
            [diGet(Foo::class)],
            $this->getParameters($params, false)
        );
    }
}
