<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\Service;

use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Traits\AttributeReader\Service\Fixtures\Main;
use Tests\Traits\AttributeReader\Service\Fixtures\MainInterface;
use Tests\Traits\AttributeReader\Service\Fixtures\NoServiceInterface;

/**
 * @covers \Kaspi\DiContainer\Attributes\Service
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 *
 * @internal
 */
class ServiceReaderTest extends TestCase
{
    protected $reader;

    public function setUp(): void
    {
        $this->reader = new class {
            use AttributeReaderTrait {
                getServiceAttribute as public;
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
        $attribute = $this->reader->getServiceAttribute(new ReflectionClass(MainInterface::class));

        $this->assertInstanceOf(Service::class, $attribute);
        $this->assertEquals(Main::class, $attribute->getIdentifier());
    }

    public function testNoneAttribute(): void
    {
        $attribute = $this->reader->getDiFactoryAttribute(new ReflectionClass(NoServiceInterface::class));

        $this->assertNull($attribute);
    }
}
