<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Kaspi\DiContainer\Attributes\Autowire;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Attributes\Autowire
 */
class AutowireTest extends TestCase
{
    public function testAutowireWithDefault(): void
    {
        $a = new Autowire();

        $this->assertEquals('', $a->getIdentifier());
        $this->assertFalse($a->isSingleton());
    }

    public function testAutowireDefinedArgs(): void
    {
        $a = new Autowire('service.a', true);

        $this->assertEquals('service.a', $a->getIdentifier());
        $this->assertTrue($a->isSingleton());
    }
}
