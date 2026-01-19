<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\ClassOne;

/**
 * @internal
 */
#[CoversNothing]
class DiFactoryTest extends TestCase
{
    public function testDiFactory(): void
    {
        $container = (new DiContainerBuilder())->build();

        $myClass = $container->get(ClassOne::class);

        self::assertEquals('Piter', $myClass->name);
        self::assertEquals(22, $myClass->age);
        self::assertSame($myClass, $container->get(ClassOne::class));
    }
}
