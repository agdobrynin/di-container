<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\TaggedAs;

use Generator;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;

/**
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 *
 * @internal
 */
class TaggedAsReaderTest extends TestCase
{
    protected $reader;

    public function setUp(): void
    {
        $this->reader = new class {
            use AttributeReaderTrait {
                getTaggedAsAttribute as public;
            }
            use DiContainerTrait; // abstract method cover.
        };
    }

    public function tearDown(): void
    {
        $this->reader = null;
    }

    public function testNonTaggedAs(): void
    {
        $fn = static fn (iterable $tagged) => '';
        $p = new ReflectionParameter($fn, 0);

        $this->assertFalse($this->reader->getTaggedAsAttribute($p)->valid());
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
        $this->expectExceptionMessage('can only be applied once per non-variadic parameter');

        /** @var Generator<TaggedAs> $res */
        $res = $this->reader->getTaggedAsAttribute($p);

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
        $res = $this->reader->getTaggedAsAttribute($p);

        $this->assertTrue($res->valid());
        $this->assertEquals('tags.handlers-opa', $res->current()->getIdentifier());
        $this->assertTrue($res->current()->isLazy());

        $res->next();

        $this->assertEquals('tags.voters-security', $res->current()->getIdentifier());
        $this->assertFalse($res->current()->isLazy());
        $this->assertEquals('getCollectionPriority', $res->current()->getPriorityDefaultMethod());
    }
}
