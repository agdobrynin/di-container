<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Kaspi\DiContainer\Attributes\Inject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Inject::class)]
class InjectTest extends TestCase
{
    public function testInjectHasIdentifier(): void
    {
        $inject = new Inject(self::class);

        $this->assertStringEndsWith('InjectTest', $inject->id);
    }

    public function testInjectDefaultIdentifier(): void
    {
        $inject = new Inject();

        $this->assertEquals('', $inject->id);
    }
}
