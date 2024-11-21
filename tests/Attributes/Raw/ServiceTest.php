<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Attributes\Service
 *
 * @internal
 */
class ServiceTest extends TestCase
{
    public function testServiceWithDefaultValue(): void
    {
        $service = new Service('id');

        $this->assertEquals('id', $service->getIdentifier());
        $this->assertFalse($service->isSingleton());
    }

    public function testServiceWithUserValue(): void
    {
        $service = new Service('id', true);

        $this->assertEquals('id', $service->getIdentifier());
        $this->assertTrue($service->isSingleton());
    }

    public function testServiceWithEmptyId(): void
    {
        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        new Service('');
    }

    public function testServiceWithSpacesId(): void
    {
        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        new Service('      ');
    }
}
