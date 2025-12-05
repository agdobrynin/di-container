<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Service;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Service;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\AttributeReader\Service\Fixtures\Main;
use Tests\AttributeReader\Service\Fixtures\MainInterface;
use Tests\AttributeReader\Service\Fixtures\NoServiceInterface;

/**
 * @internal
 */
#[CoversClass(Service::class)]
#[CoversClass(AttributeReader::class)]
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
