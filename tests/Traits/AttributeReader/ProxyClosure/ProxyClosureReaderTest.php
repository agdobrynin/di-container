<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\ProxyClosure;

use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

/**
 * @covers \Kaspi\DiContainer\Attributes\ProxyClosure
 * @covers \Kaspi\DiContainer\functionName
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 *
 * @internal
 */
class ProxyClosureReaderTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use AttributeReaderTrait;

    public function testNoneAsClosure(): void
    {
        $f = static fn (
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->assertFalse($this->getProxyClosureAttribute($p)->valid());
    }

    public function testManyAsClosureNonVariadicParameter(): void
    {
        $f = static fn (
            #[ProxyClosure('ok')]
            #[ProxyClosure('ok2')]
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/can only be applied once per non-variadic Parameter #0 \[ \<required\> string \$a \] in .+ProxyClosureReaderTest\:\:.+\(\)/');

        $this->getProxyClosureAttribute($p)->valid();
    }

    public function testInjectNonVariadicParameter(): void
    {
        $f = static fn (
            #[ProxyClosure('ok')]
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $injects = $this->getProxyClosureAttribute($p);

        $this->assertTrue($injects->valid());
        $injects->rewind();

        $this->assertInstanceOf(ProxyClosure::class, $injects->current());
        $this->assertEquals('ok', $injects->current()->getIdentifier());

        $injects->next(); // One element Inject for argument $a in function $f.

        $this->assertFalse($injects->valid());
    }

    public function testInjectVariadicParameter(): void
    {
        $f = static fn (
            #[ProxyClosure('one')]
            #[ProxyClosure('two')]
            #[ProxyClosure('three')]
            string ...$a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $injects = $this->getProxyClosureAttribute($p);

        $this->assertTrue($injects->valid());

        foreach ($injects as $inject) {
            $this->assertContains($inject->getIdentifier(), ['one', 'two', 'three']);
        }

        $this->assertFalse($injects->valid()); // All Inject fetched, generator empty.
    }
}
