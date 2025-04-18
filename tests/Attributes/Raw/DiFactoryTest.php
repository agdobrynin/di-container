<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\Attributes\Raw\Fixtures\MyDiFactory;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 *
 * @internal
 */
class DiFactoryTest extends TestCase
{
    public function testDiFactorySuccessWithDefaultValues(): void
    {
        $diFactory = new DiFactory(MyDiFactory::class);

        $this->assertEquals('Tests\Attributes\Raw\Fixtures\MyDiFactory', $diFactory->getIdentifier());
        $this->assertNull($diFactory->isSingleton());
    }

    public function testDiFactorySuccessWithUserValues(): void
    {
        $diFactory = new DiFactory(MyDiFactory::class, true);

        $this->assertTrue($diFactory->isSingleton());
    }

    public function testDiFactoryIsFail(): void
    {
        $this->expectException(AutowireExceptionInterface::class);

        new DiFactory(self::class);
    }
}
