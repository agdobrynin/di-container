<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Generator;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
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
        $this->assertNull($service->isSingleton());
    }

    public function testServiceWithUserValue(): void
    {
        $service = new Service('id', true);

        $this->assertEquals('id', $service->getIdentifier());
        $this->assertTrue($service->isSingleton());
    }

    /**
     * @dataProvider dataProviderServiceIdFail
     */
    public function testServiceWithEmptyId(string $id): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('The $id parameter must be a non-empty string.');

        new Service($id);
    }

    public function dataProviderServiceIdFail(): Generator
    {
        yield 'empty id' => [''];

        yield 'spaces id' => ['    '];
    }
}
