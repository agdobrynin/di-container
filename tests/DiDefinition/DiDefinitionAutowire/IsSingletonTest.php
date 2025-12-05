<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DiDefinitionAutowire::class)]
class IsSingletonTest extends TestCase
{
    public function testIsSingletonTrue(): void
    {
        $d = new DiDefinitionAutowire('', true);

        $this->assertTrue($d->isSingleton());
    }

    public function testIsSingletonFalse(): void
    {
        $d = new DiDefinitionAutowire('', false);

        $this->assertFalse($d->isSingleton());
    }

    public function testIsSingletonDefault(): void
    {
        $d = new DiDefinitionAutowire('');

        $this->assertNull($d->isSingleton());
    }
}
