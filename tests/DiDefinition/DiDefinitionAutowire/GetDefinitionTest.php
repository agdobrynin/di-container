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
class GetDefinitionTest extends TestCase
{
    public function testGetDefinitionFromString(): void
    {
        $d = new DiDefinitionAutowire(SuperClass::class);

        $this->assertEquals(SuperClass::class, $d->getDefinition()->getName());
    }

    public function testGetDefinitionFromReflection(): void
    {
        $d = new DiDefinitionAutowire(new ReflectionClass(SuperClass::class));

        $this->assertEquals(SuperClass::class, $d->getDefinition()->getName());
    }
}
