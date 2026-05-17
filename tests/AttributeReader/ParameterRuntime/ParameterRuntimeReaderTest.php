<?php

declare(strict_types=1);

namespace Tests\AttributeReader\ParameterRuntime;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\ParameterRuntime;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(ParameterRuntime::class)]
#[CoversClass(Helper::class)]
class ParameterRuntimeReaderTest extends TestCase
{
    public function testReadNoneVariadicManyAttributes(): void
    {
        $f = static fn (
            #[ParameterRuntime('foo')]
            #[ParameterRuntime('bar')]
            string $a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/can be applied once per non-variadic Parameter #0.+[ <required> string \$a ]/');

        AttributeReader::getAttributeOnParameter($p)->valid();
    }

    public function testReadVariadicManyAttributes(): void
    {
        $f = static fn (
            #[ParameterRuntime('foo')]
            #[ParameterRuntime('bar')]
            string ...$a
        ) => '';
        $p = new ReflectionParameter($f, 0);

        self::assertTrue(AttributeReader::getAttributeOnParameter($p)->valid());
    }
}
