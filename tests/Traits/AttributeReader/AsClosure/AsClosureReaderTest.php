<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\AsClosure;

use Kaspi\DiContainer\Attributes\AsClosure;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Attributes\AsClosure
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 *
 * @internal
 */
class AsClosureReaderTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use AttributeReaderTrait;
    use PsrContainerTrait; // 🧨 need for abstract method getContainer in AttributeReaderTrait.

    public function testNoneAsClosure(): void
    {
        $f = static fn (
            string $a
        ) => '';
        $p = new \ReflectionParameter($f, 0);

        $this->assertFalse($this->getAsClosureAttribute($p)->valid());
    }

    public function testManyAsClosureNonVariadicParameter(): void
    {
        $f = static fn (
            #[AsClosure('ok')]
            #[AsClosure('ok2')]
            string $a
        ) => '';
        $p = new \ReflectionParameter($f, 0);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('can only be applied once per non-variadic parameter');

        $this->getAsClosureAttribute($p)->valid();
    }

    public function testInjectNonVariadicParameter(): void
    {
        $f = static fn (
            #[AsClosure('ok')]
            string $a
        ) => '';
        $p = new \ReflectionParameter($f, 0);

        $injects = $this->getAsClosureAttribute($p);

        $this->assertTrue($injects->valid());
        $injects->rewind();

        $this->assertInstanceOf(AsClosure::class, $injects->current());
        $this->assertEquals('ok', $injects->current()->getIdentifier());

        $injects->next(); // One element Inject for argument $a in function $f.

        $this->assertFalse($injects->valid());
    }

    public function testInjectVariadicParameter(): void
    {
        $f = static fn (
            #[AsClosure('one')]
            #[AsClosure('two')]
            #[AsClosure('three')]
            string ...$a
        ) => '';
        $p = new \ReflectionParameter($f, 0);

        $injects = $this->getAsClosureAttribute($p);

        $this->assertTrue($injects->valid());

        $identifiers = ['one', 'two', 'three']; // Inject id argument for parameter $a in function $f

        foreach ($injects as $k => $inject) {
            $this->assertEquals($identifiers[$k], $injects->current()->getIdentifier());
        }

        $this->assertFalse($injects->valid()); // All Inject fetched, generator empty.
    }
}
