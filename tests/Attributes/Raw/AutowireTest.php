<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Kaspi\DiContainer\Attributes\Autowire;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Autowire::class)]
class AutowireTest extends TestCase
{
    public function testAutowireWithDefault(): void
    {
        $a = new Autowire();

        $this->assertEquals('', $a->id);
        $this->assertNull($a->isSingleton);
    }

    public function testAutowireDefinedArgs(): void
    {
        $a = new Autowire('service.a', true);

        $this->assertEquals('service.a', $a->id);
        $this->assertTrue($a->isSingleton);
    }
}
