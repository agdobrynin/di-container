<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Inject;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

/**
 * @internal
 */
#[CoversClass(Helper::class)]
#[CoversClass(Inject::class)]
#[CoversClass(AttributeReader::class)]
class InjectReaderTest extends TestCase
{
    public function testNoneInject(): void
    {
        $f = static fn (
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->assertFalse(AttributeReader::getAttributeOnParameter($p)->valid());
    }

    public function testManyInjectNonVariadicParameter(): void
    {
        $f = static fn (
            #[Inject]
            #[Inject]
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('can be applied once per non-variadic Parameter #0 [ <required> string $a ] in');

        AttributeReader::getAttributeOnParameter($p)->valid();
    }

    public function testInjectVariadicParameter(): void
    {
        $f = static fn (
            #[Inject('one')]
            #[Inject('two')]
            #[Inject('three')]
            string ...$a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $injects = AttributeReader::getAttributeOnParameter($p);

        $this->assertTrue($injects->valid());

        $identifiers = ['one', 'two', 'three']; // Inject id argument for parameter $a in function $f

        foreach ($injects as $k => $inject) {
            $this->assertEquals($identifiers[$k], $injects->current()->id);
        }

        $this->assertFalse($injects->valid()); // All Inject fetched, generator empty.
    }
}
