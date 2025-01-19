<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Attributes\Raw\Fixtures\MyDiFactory;

/**
 * @internal
 */
#[CoversClass(DiFactory::class)]
class DiFactoryTest extends TestCase
{
    public function testDiFactorySuccessWithDefaultValues(): void
    {
        $diFactory = new DiFactory(MyDiFactory::class);

        $this->assertEquals('Tests\Attributes\Raw\Fixtures\MyDiFactory', $diFactory->getIdentifier());
        $this->assertFalse($diFactory->isSingleton());
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
