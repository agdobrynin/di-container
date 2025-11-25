<?php

declare(strict_types=1);

namespace Tests\AttributeReader\Tag;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Tag;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\AttributeReader\Tag\Fixtures\NoneTaggedClass;
use Tests\AttributeReader\Tag\Fixtures\TaggedClass;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\Tag
 *
 * @internal
 */
class TagReaderTest extends TestCase
{
    public function testNoneTaggedClassReader(): void
    {
        $result = AttributeReader::getTagAttribute(new ReflectionClass(NoneTaggedClass::class));

        $this->assertFalse($result->valid());
    }

    public function testTaggedClassReader(): void
    {
        $result = AttributeReader::getTagAttribute(new ReflectionClass(TaggedClass::class));

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
