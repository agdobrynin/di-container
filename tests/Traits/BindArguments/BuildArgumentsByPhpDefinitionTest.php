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

use function func_get_args;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;
use function Kaspi\DiContainer\diValue;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\diValue
 * @covers \Kaspi\DiContainer\functionNameByParameter
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait::getParameterType
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 *
 * @internal
 */
class BuildArgumentsByPhpDefinitionTest extends TestCase
{
    use BindArgumentsTrait;
    use DiContainerTrait;
    private DiContainerInterface $containerMock;

    public function setUp(): void
    {
        $this->bindArguments();
        $this->containerMock = $this->createMock(DiContainerInterface::class);
    }

    public function testGetWithoutParameters(): void
    {
        $fn = static fn () => '';

        $this->bindArguments('one', 'two', diGet('services.logger_file'));

        self::assertEquals(
            ['one', 'two', diGet('services.logger_file')],
            $this->buildArguments(new ReflectionFunction($fn), false)
        );
    }

    public function testResolveByParameterTypeByParameterNameByDefaultValue(): void
    {
        $fn = static fn (ArrayIterator $iterator, $dto = new stdClass()): array => [$iterator, $dto];

        $this->containerMock->method('has')
            ->with('ArrayIterator')
            ->willReturn(true)
        ;
        $this->containerMock->method('get')
            ->with('ArrayIterator')
            ->willReturn(new ArrayIterator())
        ;

        $this->setContainer($this->containerMock);

        $args = $this->buildArguments(new ReflectionFunction($fn), false);

        self::assertEquals([diGet('ArrayIterator')], $args);
    }

    public function testGetParameterTypeWithDefaultValue(): void
    {
        $fn = static fn (Bar|Foo $fooBar = new Baz()): Bar|Foo => $fooBar;

        $this->containerMock->method('has')
            ->willReturnMap([
                [Foo::class, true],
                [Bar::class, true],
            ])
        ;

        $this->setContainer($this->containerMock);

        self::assertEmpty($this->buildArguments(new ReflectionFunction($fn), false));
    }

    public function testGetParameterTypeOnce(): void
    {
        $fn = static fn (Bar|Foo $fooBar = new Baz()): Bar|Foo => $fooBar;

        $this->containerMock->method('has')
            ->willReturnMap([
                [Bar::class, true],
                [Foo::class, false],
            ])
        ;

        $this->setContainer($this->containerMock);

        self::assertEquals(
            [diGet(Bar::class)],
            $this->buildArguments(new ReflectionFunction($fn), false)
        );
    }

    public function testGetParameterIntersectionTypeWithDefaultValue(): void
    {
        $fn = static fn (Bar&Foo $fooBar = new Baz()): Bar&Foo => $fooBar;

        $this->containerMock->method('has')
            ->willReturnMap([
                [Bar::class, true],
                [Foo::class, true],
            ])
        ;

        $this->setContainer($this->containerMock);

        self::assertEmpty($this->buildArguments(new ReflectionFunction($fn), false));
    }

    public function testExceptionGetParameterIntersectionType(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/^Cannot automatically resolve dependency.+Bar&.+Foo \$fooBar/');

        $fn = static fn (Bar&Foo $fooBar): Bar&Foo => $fooBar;

        $this->containerMock->method('has')
            ->willReturnMap([
                [Bar::class, true],
                [Foo::class, true],
            ])
        ;

        $this->setContainer($this->containerMock);

        $this->buildArguments(new ReflectionFunction($fn), false);
    }

    public function testVariadicParameterWithoutArgument(): void
    {
        $fn = static fn (Bar $bar, Foo ...$foo): array => [$bar, $foo];

        $this->containerMock->method('has')
            ->willReturnMap([
                [Bar::class, true],
                [Foo::class, true],
            ])
        ;

        $this->setContainer($this->containerMock);

        self::assertEquals(
            [diGet(Bar::class)],
            $this->buildArguments(new ReflectionFunction($fn), false)
        );
    }

    public function testVariadicParameterBindArgumentAsNamedArgument(): void
    {
        $fn = static fn (Bar $bar, Foo ...$foo): array => [$bar, $foo];

        $this->containerMock->method('has')
            ->willReturnMap([
                [Bar::class, true],
                [Foo::class, true],
            ])
        ;

        $this->setContainer($this->containerMock);

        $this->bindArguments(
            foo: diGet(Foo::class),
            foo_1: diGet(Baz::class),
        );

        self::assertEquals(
            [
                0 => diGet(Bar::class),
                'foo' => diGet(Foo::class),
                'foo_1' => diGet(Baz::class),
            ],
            $this->buildArguments(new ReflectionFunction($fn), false)
        );
    }

    public function testVariadicParameterBindArgumentAsIndexArgument(): void
    {
        $fn = static fn (Bar $bar, Foo ...$foo): array => [$bar, $foo];

        $this->setContainer($this->containerMock);

        $this->bindArguments(
            diGet(Bar::class),
            diGet(Foo::class),
            diGet(Baz::class),
        );

        self::assertEquals(
            [
                0 => diGet(Bar::class),
                1 => diGet(Foo::class),
                2 => diGet(Baz::class),
            ],
            $this->buildArguments(new ReflectionFunction($fn), false)
        );
    }

    public function testDefaultParameter(): void
    {
        $fn = static fn (Foo $foo, Bar|Foo $bar = new Baz()): array => [$foo, $bar];

        $this->containerMock->method('has')
            ->willReturnMap([
                [Foo::class, true],
                [Bar::class, true],
            ])
        ;

        $this->setContainer($this->containerMock);

        self::assertEquals(
            [diGet(Foo::class)],
            $this->buildArguments(new ReflectionFunction($fn), false)
        );
    }

    public function testPassNotDeclarationParameter(): void
    {
        $fn = static fn (Foo $foo): array => [$foo, func_get_args()];

        $this->containerMock->method('has')
            ->willReturnMap([
                [Foo::class, true],
            ])
        ;

        $this->setContainer($this->containerMock);

        $this->bindArguments(
            diGet(Foo::class),
            diAutowire(Baz::class)
                ->bindArguments('secure_string')
                ->setup('__invoke'),
            diValue(new stdClass()),
        );

        self::assertEquals(
            [
                diGet(Foo::class),
                diAutowire(Baz::class)
                    ->bindArguments('secure_string')
                    ->setup('__invoke'),
                diValue(new stdClass()),
            ],
            $this->buildArguments(new ReflectionFunction($fn), false)
        );
    }
}
