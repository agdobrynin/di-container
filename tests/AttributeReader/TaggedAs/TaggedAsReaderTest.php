<?php

declare(strict_types=1);

namespace Tests\AttributeReader\TaggedAs;

use Generator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\Helper
 *
 * @internal
 */
class TaggedAsReaderTest extends TestCase
{
    public function testNonTaggedAs(): void
    {
        $fn = static fn (iterable $tagged) => '';
        $p = new ReflectionParameter($fn, 0);

        $this->assertFalse(AttributeReader::getTaggedAsAttribute($p)->valid());
    }

    public function testTaggedAsManyForNonVariadic(): void
    {
        $fn = static fn (
            #[TaggedAs('tags.handlers-opa')]
            #[TaggedAs('tags.voters-security')]
            iterable $tagged
        ) => '';
        $p = new ReflectionParameter($fn, 0);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/can only be applied once per non-variadic Parameter #0.+[ <required> (iterable|Traversable\|array) \$tagged ]/');

        /** @var Generator<TaggedAs> $res */
        $res = AttributeReader::getTaggedAsAttribute($p);

        $this->assertFalse($res->valid());
    }

    public function testTaggedAsManyForVariadic(): void
    {
        $fn = static fn (
            #[TaggedAs('tags.handlers-opa')]
            #[TaggedAs('tags.voters-security', false, 'getCollectionPriority')]
            ...$tagged
        ) => [];
        $p = new ReflectionParameter($fn, 0);

        /** @var Generator<TaggedAs> $res */
        $res = AttributeReader::getTaggedAsAttribute($p);

        $this->assertTrue($res->valid());
        $this->assertEquals('tags.handlers-opa', $res->current()->getIdentifier());
        $this->assertTrue($res->current()->isLazy());

        $res->next();

        $this->assertEquals('tags.voters-security', $res->current()->getIdentifier());
        $this->assertFalse($res->current()->isLazy());
        $this->assertEquals('getCollectionPriority', $res->current()->getPriorityDefaultMethod());
    }
}
