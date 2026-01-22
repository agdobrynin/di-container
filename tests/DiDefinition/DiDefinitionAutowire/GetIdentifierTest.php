<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\SuperClass;

/**
 * @internal
 */
#[CoversClass(DiDefinitionAutowire::class)]
class GetIdentifierTest extends TestCase
{
    public function testGetIdentifierFromString(): void
    {
        $d = new DiDefinitionAutowire(SuperClass::class);

        $this->assertEquals(SuperClass::class, $d->getIdentifier());
    }

    public function testGetIdentifierFromReflection(): void
    {
        $d = new DiDefinitionAutowire(new ReflectionClass(SuperClass::class));

        $this->assertEquals(SuperClass::class, $d->getIdentifier());
    }
}
