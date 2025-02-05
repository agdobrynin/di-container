<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\TaggedDefaultPriorityMethod;

use Kaspi\DiContainer\Attributes\TagDefaultPriorityMethod;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use Tests\Traits\AttributeReader\TaggedDefaultPriorityMethod\Fixtures\EmptyClass;
use Tests\Traits\AttributeReader\TaggedDefaultPriorityMethod\Fixtures\TaggedClass;
use Tests\Traits\AttributeReader\TaggedDefaultPriorityMethod\Fixtures\TaggedClassWithEmptyDef;

/**
 * @covers \Kaspi\DiContainer\Attributes\TagDefaultPriorityMethod
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 *
 * @internal
 */
class TaggedDefaultPriorityMethodTest extends TestCase
{
    protected $reader;

    public function setUp(): void
    {
        $this->reader = new class {
            use AttributeReaderTrait {
                getTagDefaultPriorityMethod as public;
            }
            use DiContainerTrait; // abstract method cover.
        };
    }

    public function testReadAttribute(): void
    {
        $res = $this->reader->getTagDefaultPriorityMethod(new \ReflectionClass(TaggedClass::class));

        $this->assertInstanceOf(TagDefaultPriorityMethod::class, $res);
        $this->assertEquals('getPriority', $res->getIdentifier());
    }

    public function testReadAttributeWithEmptyIdentifier(): void
    {
        $res = $this->reader->getTagDefaultPriorityMethod(new \ReflectionClass(TaggedClassWithEmptyDef::class));

        $this->assertInstanceOf(TagDefaultPriorityMethod::class, $res);
        $this->assertEquals('', $res->getIdentifier());
    }

    public function testReadAttributeEmpty(): void
    {
        $this->assertNull($this->reader->getTagDefaultPriorityMethod(new \ReflectionClass(EmptyClass::class)));
    }
}
