<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\Service;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Service;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Traits\AttributeReader\Service\Fixtures\Main;
use Tests\Traits\AttributeReader\Service\Fixtures\MainInterface;
use Tests\Traits\AttributeReader\Service\Fixtures\NoServiceInterface;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\Service
 *
 * @internal
 */
class ServiceReaderTest extends TestCase
{
    public function testHasOneAttribute(): void
    {
        $attribute = AttributeReader::getServiceAttribute(new ReflectionClass(MainInterface::class));

        $this->assertInstanceOf(Service::class, $attribute);
        $this->assertEquals(Main::class, $attribute->getIdentifier());
    }

    public function testNoneAttribute(): void
    {
        $attribute = AttributeReader::getDiFactoryAttribute(new ReflectionClass(NoServiceInterface::class));

        $this->assertNull($attribute);
    }
}
