<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\Tag;

use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Traits\AttributeReader\Tag\Fixtures\NoneTaggedClass;
use Tests\Traits\AttributeReader\Tag\Fixtures\TaggedClass;

/**
 * @covers \Kaspi\DiContainer\Attributes\Tag
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 *
 * @internal
 */
class TagReaderTest extends TestCase
{
    protected $reader;

    public function setUp(): void
    {
        $this->reader = new class {
            use AttributeReaderTrait {
                // @return \Generator<Tag>
                getTagAttribute as public;
            }
            use DiContainerTrait; // abstract method cover.
        };
    }

    public function tearDown(): void
    {
        $this->reader = null;
    }

    public function testNoneTaggedClassReader(): void
    {
        $result = $this->reader->getTagAttribute(new ReflectionClass(NoneTaggedClass::class));

        $this->assertFalse($result->valid());
    }

    public function testTaggedClassReader(): void
    {
        $result = $this->reader->getTagAttribute(new ReflectionClass(TaggedClass::class));

        $this->assertTrue($result->valid());
        $this->assertInstanceOf(Tag::class, $result->current());
        $this->assertEquals('tags.handler-one', $result->current()->getIdentifier());
        $this->assertEquals([], $result->current()->getOptions());

        $result->next();
        $this->assertInstanceOf(Tag::class, $result->current());
        $this->assertEquals('tags.handler-two', $result->current()->getIdentifier());
        $this->assertEquals(['priority' => 100], $result->current()->getOptions());
        $this->assertEquals(150, $result->current()->getPriority());

        $result->next();
        $this->assertFalse($result->valid());
    }
}
