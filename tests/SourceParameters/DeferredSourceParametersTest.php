<?php

declare(strict_types=1);

namespace Tests\SourceParameters;

use Kaspi\DiContainer\Parameters\AbstractSourceParameters;
use Kaspi\DiContainer\Parameters\DeferredSourceParameters;
use Kaspi\DiContainer\Parameters\SourceParameterItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractSourceParameters::class)]
#[CoversClass(DeferredSourceParameters::class)]
#[CoversClass(SourceParameterItem::class)]
class DeferredSourceParametersTest extends TestCase
{
    public function testLazyInitParameters(): void
    {
        $src = static fn () => ['foo' => 'bar'];
        $p = new DeferredSourceParameters($src);

        self::assertSame('bar', $p->get('foo'));
    }
}
