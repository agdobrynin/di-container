<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\Service;

use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
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
                AttributeReaderTrait::getServiceAttribute as public;
                AttributeReaderTrait::getDiFactoryAttribute as public;
            }
            use PsrContainerTrait; // abstract method cover.
        };
    }

    public function tearDown(): void
    {
        $this->reader = null;
    }

    public function testHasOneAttribute(): void
    {
        $attribute = $this->reader->getServiceAttribute(new \ReflectionClass(MainInterface::class));

        $this->assertInstanceOf(Service::class, $attribute);
        $this->assertEquals(Main::class, $attribute->getIdentifier());
    }

    public function testNoneAttribute(): void
    {
        $attribute = $this->reader->getDiFactoryAttribute(new \ReflectionClass(NoServiceInterface::class));

        $this->assertNull($attribute);
    }
}
