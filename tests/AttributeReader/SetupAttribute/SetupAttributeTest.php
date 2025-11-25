<?php

declare(strict_types=1);

namespace Tests\AttributeReader\SetupAttribute;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\AttributeReader\SetupAttribute\Fixtures\SetupImmutableOnMethods;
use Tests\AttributeReader\SetupAttribute\Fixtures\SetupOnMethods;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\Setup
 * @covers \Kaspi\DiContainer\Attributes\SetupImmutable
 * @covers \Kaspi\DiContainer\Helper
 *
 * @internal
 */
class SetupAttributeTest extends TestCase
{
    public function testReadSetupAttribute(): void
    {
        /** @var Setup[] $res */
        $res = [...AttributeReader::getSetupAttribute(new ReflectionClass(SetupOnMethods::class))];

        self::assertCount(3, $res);

        self::assertEquals('__construct', $res[0]->getIdentifier());
        self::assertEquals(['x'], $res[0]->getArguments());

        self::assertEquals('__destruct', $res[1]->getIdentifier());
        self::assertEmpty($res[1]->getArguments());

        self::assertEquals('demo2', $res[2]->getIdentifier());
        self::assertEquals([new DiGet('services.foo')], $res[2]->getArguments());
    }

    public function testReadSetupImmutableAttribute(): void
    {
        /** @var SetupImmutable[] $res */
        $res = [...AttributeReader::getSetupAttribute(new ReflectionClass(SetupImmutableOnMethods::class))];

        self::assertCount(3, $res);

        self::assertEquals('__construct', $res[0]->getIdentifier());
        self::assertEquals(['bar'], $res[0]->getArguments());

        self::assertEquals('__destruct', $res[1]->getIdentifier());
        self::assertEmpty($res[1]->getArguments());

        self::assertEquals('demo2', $res[2]->getIdentifier());
        self::assertEquals([new DiGet('services.foo')], $res[2]->getArguments());
    }
}
