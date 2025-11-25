<?php

declare(strict_types=1);

namespace Tests\AttributeReader\InjectCallable;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\InjectByCallable
 * @covers \Kaspi\DiContainer\Helper
 *
 * @internal
 */
class InjectCallableTest extends TestCase
{
    public function testInjectByCallableEmpty(): void
    {
        $f = static fn (
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->assertFalse(AttributeReader::getInjectByCallableAttribute($p)->valid());
    }

    public function testManyInjectByCallableNonVariadicParameter(): void
    {
        $f = static fn (
            #[InjectByCallable('func')]
            #[InjectByCallable('func2')]
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/can only be applied once per non-variadic Parameter #0.+[ <required> string \$a ].+InjectCallableTest::.+()/');

        AttributeReader::getInjectByCallableAttribute($p)->valid();
    }

    public function testInjectByCallableNonVariadicParameter(): void
    {
        $f = static fn (
            #[InjectByCallable('func')]
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $injects = AttributeReader::getInjectByCallableAttribute($p);

        $this->assertTrue($injects->valid());

        $this->assertInstanceOf(InjectByCallable::class, $injects->current());
        $this->assertEquals('func', $injects->current()->getIdentifier());

        $injects->next(); // One element Inject for argument $a in function $f.

        $this->assertFalse($injects->valid());
    }

    public function testInjectByCallableVariadicParameter(): void
    {
        $f = static fn (
            #[InjectByCallable('func1')]
            #[InjectByCallable('func2')]
            #[InjectByCallable('func3')]
            string ...$a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $injects = AttributeReader::getInjectByCallableAttribute($p);

        $this->assertTrue($injects->valid());

        $this->assertInstanceOf(InjectByCallable::class, $injects->current());
        $this->assertEquals('func1', $injects->current()->getIdentifier());

        $injects->next();

        $this->assertInstanceOf(InjectByCallable::class, $injects->current());
        $this->assertEquals('func2', $injects->current()->getIdentifier());

        $injects->next();

        $this->assertInstanceOf(InjectByCallable::class, $injects->current());
        $this->assertEquals('func3', $injects->current()->getIdentifier());

        $injects->next();

        $this->assertFalse($injects->valid());
    }
}
