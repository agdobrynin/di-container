<?php

declare(strict_types=1);

namespace Tests\AttributeReader\SetupAttribute;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\Attributes\SetupPriority;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;
use Kaspi\DiContainer\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\AttributeReader\SetupAttribute\Fixtures\SetupImmutableOnMethods;
use Tests\AttributeReader\SetupAttribute\Fixtures\SetupImmutablePriorityOnMethods;
use Tests\AttributeReader\SetupAttribute\Fixtures\SetupOnMethods;
use Tests\AttributeReader\SetupAttribute\Fixtures\SetupPriorityOnMethods;

/**
 * @internal
 */
#[
    CoversClass(Helper::class),
    CoversClass(SetupImmutable::class),
    CoversClass(Setup::class),
    CoversClass(AttributeReader::class),
    CoversClass(DiGet::class),
    CoversClass(SetupPriority::class)
]
class SetupAttributeTest extends TestCase
{
    public function testReadSetupAttribute(): void
    {
        /** @var Setup[] $res */
        $res = [...AttributeReader::getSetupAttribute(new ReflectionClass(SetupOnMethods::class))];

        self::assertCount(3, $res);

        self::assertEquals('__construct', $res[0]->getMethod());
        self::assertEquals(['x'], $res[0]->arguments);

        self::assertEquals('__destruct', $res[1]->getMethod());
        self::assertEmpty($res[1]->arguments);

        self::assertEquals('demo2', $res[2]->getMethod());
        self::assertEquals([new DiGet('services.foo')], $res[2]->arguments);
    }

    public function testReadSetupAttributeWithPriority(): void
    {
        /** @var Setup[] $res */
        $res = [...AttributeReader::getSetupAttribute(new ReflectionClass(SetupPriorityOnMethods::class))];

        self::assertCount(3, $res);

        self::assertEquals('demo2', $res[0]->getMethod());
        self::assertEquals([new DiGet('services.foo')], $res[0]->arguments);

        self::assertEquals('__construct', $res[1]->getMethod());
        self::assertEquals(['x'], $res[1]->arguments);

        self::assertEquals('__destruct', $res[2]->getMethod());
        self::assertEmpty($res[2]->arguments);
    }

    public function testReadSetupImmutableAttribute(): void
    {
        /** @var SetupImmutable[] $res */
        $res = [...AttributeReader::getSetupAttribute(new ReflectionClass(SetupImmutableOnMethods::class))];

        self::assertCount(3, $res);

        self::assertEquals('__construct', $res[0]->getMethod());
        self::assertEquals(['bar'], $res[0]->arguments);

        self::assertEquals('__destruct', $res[1]->getMethod());
        self::assertEmpty($res[1]->arguments);

        self::assertEquals('demo2', $res[2]->getMethod());
        self::assertEquals([new DiGet('services.foo')], $res[2]->arguments);
    }

    public function testReadSetupImmutableAttributeWithPriority(): void
    {
        /** @var SetupImmutable[] $res */
        $res = [...AttributeReader::getSetupAttribute(new ReflectionClass(SetupImmutablePriorityOnMethods::class))];

        self::assertCount(3, $res);

        self::assertEquals('__destruct', $res[0]->getMethod());
        self::assertEmpty($res[0]->arguments);

        self::assertEquals('demo2', $res[1]->getMethod());
        self::assertEquals([new DiGet('services.foo')], $res[1]->arguments);

        self::assertEquals('__construct', $res[2]->getMethod());
        self::assertEquals(['bar'], $res[2]->arguments);
    }
}
