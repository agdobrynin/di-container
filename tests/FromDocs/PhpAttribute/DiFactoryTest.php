<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\ClassOne;
use Tests\FromDocs\PhpAttribute\Fixtures\OtherClass;

/**
 * @internal
 */
#[CoversNothing]
class DiFactoryTest extends TestCase
{
    public function testClassResolveViaDiFactory(): void
    {
        $container = (new DiContainerBuilder())->build();

        $myClass = $container->get(ClassOne::class);

        self::assertEquals('Piter', $myClass->name);
        self::assertEquals(22, $myClass->age);
        self::assertSame($myClass, $container->get(ClassOne::class));
    }

    public function testParameterResolveViaDiFactory(): void
    {
        $container = (new DiContainerBuilder())->build();

        $otherClass = $container->get(OtherClass::class);

        self::assertEquals('Piter', $otherClass->classOne->name);
        self::assertEquals(22, $otherClass->classOne->age);
    }
}
