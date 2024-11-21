<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Kaspi\DiContainer\Attributes\Inject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 *
 * @internal
 */
class InjectTest extends TestCase
{
    public function testInjectHasIdentifier(): void
    {
        $inject = new Inject(self::class);

        $this->assertStringEndsWith('InjectTest', $inject->getIdentifier());
    }

    public function testInjectDefaultIdentifier(): void
    {
        $inject = new Inject();

        $this->assertEquals('', $inject->getIdentifier());
    }
}
