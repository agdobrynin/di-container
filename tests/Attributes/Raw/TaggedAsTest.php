<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Generator;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 *
 * @internal
 */
class TaggedAsTest extends TestCase
{
    /**
     * @dataProvider dataProviderFail
     */
    public function testTagFail(string $name): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('parameter must be a non-empty string');

        new TaggedAs($name);
    }

    public static function dataProviderFail(): Generator
    {
        yield 'empty string' => [''];

        yield 'string with spaces' => ['   '];
    }

    public function testTaggedAsDefault(): void
    {
        $tag = new TaggedAs('tags.handler-one');

        $this->assertEquals('tags.handler-one', $tag->getIdentifier());
        $this->assertTrue($tag->isLazy());
        $this->assertNull($tag->getPriorityDefaultMethod());
        $this->assertTrue($tag->isUseKeys());
        $this->assertNull($tag->getKey());
        $this->assertNull($tag->getKeyDefaultMethod());
        $this->assertEquals([], $tag->getContainerIdExclude());
        $this->assertTrue($tag->isSelfExclude());
    }

    public function testTaggedAsNotDefault(): void
    {
        $tag = new TaggedAs('tags.handler-one', false, 'getPriority', false, 'key', 'getKey', ['id1', 'id2'], false);

        $this->assertFalse($tag->isLazy());
        $this->assertEquals('getPriority', $tag->getPriorityDefaultMethod());
        $this->assertFalse($tag->isUseKeys());
        $this->assertEquals('key', $tag->getKey());
        $this->assertEquals('getKey', $tag->getKeyDefaultMethod());
        $this->assertEquals(['id1', 'id2'], $tag->getContainerIdExclude());
        $this->assertFalse($tag->isSelfExclude());
    }
}
