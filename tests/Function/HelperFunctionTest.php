<?php

declare(strict_types=1);

namespace Tests\Function;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diGet;
use function Kaspi\DiContainer\diProxyClosure;
use function Kaspi\DiContainer\diReference;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\diProxyClosure
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\diTaggedAs
 *
 * @internal
 */
class HelperFunctionTest extends TestCase
{
    public function testFunctiondiGet(): void
    {
        $def = diGet('ok');

        $this->assertInstanceOf(DiDefinitionGet::class, $def);
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

    public function testFunctionDiProxyClosure(): void
    {
        $def = diProxyClosure(self::class);

        $this->assertInstanceOf(DiDefinitionProxyClosure::class, $def);
    }

    public function testFunctionDiTaggedAsWithDefaultParams(): void
    {
        $def = diTaggedAs('tags.security.voters');

        $this->assertInstanceOf(DiDefinitionTaggedAs::class, $def);
    }

    public function testFunctionDiTaggedAsWithAllParams(): void
    {
        $def = diTaggedAs('tags.security.voters', false, 'getPriorityForCollection', true);

        $this->assertInstanceOf(DiDefinitionTaggedAs::class, $def);
    }

    public function testDeprecatedFunctionDiReference(): void
    {
        $def = diReference('ok');

        $this->assertInstanceOf(DiDefinitionGet::class, $def);
        $this->assertEquals('ok', $def->getDefinition());
    }
}
