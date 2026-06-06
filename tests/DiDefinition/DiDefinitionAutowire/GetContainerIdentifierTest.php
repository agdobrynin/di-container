<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SuperClass;

/**
 * @internal
 */
#[CoversClass(DiDefinitionAutowire::class)]
class GetContainerIdentifierTest extends TestCase
{
    public function testContainerIdentifier(): void
    {
        $d = new DiDefinitionAutowire(SuperClass::class);

        self::assertNull($d->getContainerIdentifier());

        $d->setContainerIdentifier('foo');

        $this->assertEquals('foo', $d->getContainerIdentifier());
    }
}
