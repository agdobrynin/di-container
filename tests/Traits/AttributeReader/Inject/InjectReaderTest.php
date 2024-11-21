<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\Inject;

use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getInjectAttribute
 *
 * @internal
 */
class InjectReaderTest extends TestCase
{
    protected $reader;

    public function setUp(): void
    {
        $this->reader = new class {
            use AttributeReaderTrait;
            use PsrContainerTrait; // abstract method cover.
        };
    }

    public function tearDown(): void
    {
        $this->reader = null;
    }

    public function testNoneInject(): void
    {
        $p = (new \ReflectionFunction(fn (string $a) => $a.'🚩'))->getParameters()[0];

        $result = $this->reader->getInjectAttribute($p);

        $this->assertInstanceOf(\Generator::class, $result);
        $this->assertFalse($result->valid());
    }
}
