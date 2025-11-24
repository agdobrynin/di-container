<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\SetupAttribute;

use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Traits\AttributeReader\SetupAttribute\Fixtures\SetupImmutableOnMethods;
use Tests\Traits\AttributeReader\SetupAttribute\Fixtures\SetupOnMethods;

/**
 * @covers \Kaspi\DiContainer\Attributes\Setup
 * @covers \Kaspi\DiContainer\Attributes\SetupImmutable
 * @covers \Kaspi\DiContainer\Helper
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 *
 * @internal
 */
class SetupAttributeTest extends TestCase
{
    protected object $reader;

    public function setUp(): void
    {
        $this->reader = new class {
            use AttributeReaderTrait {
                getSetupAttribute as public;
            }
        };
    }

    public function testReadSetupAttribute(): void
    {
        /** @var Setup[] $res */
        $res = [...$this->reader->getSetupAttribute(new ReflectionClass(SetupOnMethods::class))];

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
        $res = [...$this->reader->getSetupAttribute(new ReflectionClass(SetupImmutableOnMethods::class))];

        self::assertCount(3, $res);

        self::assertEquals('__construct', $res[0]->getIdentifier());
        self::assertEquals(['bar'], $res[0]->getArguments());

        self::assertEquals('__destruct', $res[1]->getIdentifier());
        self::assertEmpty($res[1]->getArguments());

        self::assertEquals('demo2', $res[2]->getIdentifier());
        self::assertEquals([new DiGet('services.foo')], $res[2]->getArguments());
    }
}
