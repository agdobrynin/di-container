<?php

declare(strict_types=1);

namespace Tests\Function;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionReference;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diReference;
use function Kaspi\DiContainer\diValue;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\diValue
 *
 * @internal
 */
class HelperFunctionTest extends TestCase
{
    public function testFunctionDiValue(): void
    {
        $def = diValue(null);

        $this->assertInstanceOf(DiDefinitionValue::class, $def);
        $this->assertNull($def->getDefinition());
    }

    public function testFunctionDiReference(): void
    {
        $def = diReference('ok');

        $this->assertInstanceOf(DiDefinitionReference::class, $def);
        $this->assertEquals('ok', $def->getDefinition());
    }

    public function testFunctionDiCallable(): void
    {
        $def = diCallable(static fn () => 'ok', true);

        $this->assertInstanceOf(DiDefinitionCallable::class, $def);
        $this->assertTrue($def->isSingleton());
    }

    public function testFunctionDiAutowire(): void
    {
        $def = diAutowire(self::class, true);

        $this->assertInstanceOf(DiDefinitionAutowire::class, $def);
        $this->assertTrue($def->isSingleton());
    }
}
