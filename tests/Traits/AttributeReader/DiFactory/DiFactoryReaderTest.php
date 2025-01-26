<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\DiFactory;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use Tests\Traits\AttributeReader\DiFactory\Fixtures\Main;
use Tests\Traits\AttributeReader\DiFactory\Fixtures\MainFirstDiFactory;
use Tests\Traits\AttributeReader\DiFactory\Fixtures\NoDiFactories;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 *
 * @internal
 */
class DiFactoryReaderTest extends TestCase
{
    protected $reader;

    public function setUp(): void
    {
        $this->reader = new class {
            use AttributeReaderTrait {
                getDiFactoryAttribute as public;
            }
            use DiContainerTrait; // abstract method cover.
        };
    }

    public function tearDown(): void
    {
        $this->reader = null;
    }

    public function testHasOneAttribute(): void
    {
        $attribute = $this->reader->getDiFactoryAttribute(new \ReflectionClass(Main::class));

        $this->assertInstanceOf(DiFactory::class, $attribute);
        $this->assertEquals(MainFirstDiFactory::class, $attribute->getIdentifier());
    }

    public function testNoneAttribute(): void
    {
        $attribute = $this->reader->getDiFactoryAttribute(new \ReflectionClass(NoDiFactories::class));

        $this->assertNull($attribute);
    }
}
