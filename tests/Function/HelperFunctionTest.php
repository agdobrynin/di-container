<?php

declare(strict_types=1);

namespace Tests\Function;

use Kaspi\DiContainer\DiDefinition\DiDefinitionReference;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionProxyClosureInterface;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diGet;
use function Kaspi\DiContainer\diProxyClosure;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\diProxyClosure
 * @covers \Kaspi\DiContainer\diReference
 *
 * @internal
 */
class HelperFunctionTest extends TestCase
{
    public function testFunctiondiGet(): void
    {
        $def = diGet('ok');

        $this->assertInstanceOf(DiDefinitionInterface::class, $def);
        $this->assertEquals('ok', $def->getDefinition());
    }

    public function testFunctionDiCallable(): void
    {
        $def = diCallable(static fn () => 'ok', true);

        $this->assertInstanceOf(DiDefinitionAutowireInterface::class, $def);
        $this->assertTrue($def->isSingleton());
    }

    public function testFunctionDiAutowire(): void
    {
        $def = diAutowire(self::class, true);

        $this->assertInstanceOf(DiDefinitionAutowireInterface::class, $def);
        $this->assertTrue($def->isSingleton());
    }

    public function testFunctionDiProxyClosure(): void
    {
        $def = diProxyClosure(self::class);

        $this->assertInstanceOf(DiDefinitionProxyClosureInterface::class, $def);
    }

    public function testDepricatedFunctionDiReference(): void
    {
        $def = diReference('ok');

        $this->assertInstanceOf(DiDefinitionReference::class, $def);
        $this->assertEquals('ok', $def->getDefinition());
    }
}
