<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Autowire;

use ArrayAccess;
use ArrayIterator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;
use Tests\AttributeReader\Autowire\Fixtures\DoSomething;

/**
 * @internal
 */
#[CoversClass(Autowire::class)]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Helper::class)]
class AutowireOnParameterTest extends TestCase
{
    public function testAutowireOnParameterRepeatedFail(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('he php attribute can be applied once per non-variadic Parameter #0');

        $fn = static fn (#[Autowire(ArrayAccess::class)] #[Autowire(ArrayIterator::class)] mixed $p) => true;

        AttributeReader::getAttributeOnParameter(new ReflectionParameter($fn, 0))->valid();
    }

    public function testAutowireOnParameterWithOtherAttribute(): void
    {
        $fn = static fn (#[DoSomething] #[Autowire(ArrayAccess::class)] mixed ...$p) => true;

        $attrs = AttributeReader::getAttributeOnParameter(new ReflectionParameter($fn, 0));

        self::assertInstanceOf(Autowire::class, $attrs->current());

        $attrs->next();

        self::assertFalse($attrs->valid());
    }
}
