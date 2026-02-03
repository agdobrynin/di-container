<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Generator;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Service::class)]
class ServiceTest extends TestCase
{
    public function testServiceWithDefaultValue(): void
    {
        $service = new Service('id');

        $this->assertEquals('id', $service->id);
        $this->assertNull($service->isSingleton);
    }

    public function testServiceWithUserValue(): void
    {
        $service = new Service('id', true);

        $this->assertEquals('id', $service->id);
        $this->assertTrue($service->isSingleton);
    }

    #[DataProvider('dataProviderServiceIdFail')]
    public function testServiceWithEmptyId(string $id): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('The $id parameter must be a non-empty string.');

        new Service($id);
    }

    public static function dataProviderServiceIdFail(): Generator
    {
        yield 'empty id' => [''];

        yield 'spaces id' => ['    '];
    }
}
