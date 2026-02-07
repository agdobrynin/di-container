<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Generator;
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TaggedAs::class)]
class TaggedAsTest extends TestCase
{
    #[DataProvider('dataProviderFail')]
    public function testTagFail(string $name): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('The $name parameter must be a non-empty string.');

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

        $this->assertEquals('tags.handler-one', $tag->name);
        $this->assertTrue($tag->isLazy);
        $this->assertNull($tag->priorityDefaultMethod);
        $this->assertTrue($tag->useKeys);
        $this->assertNull($tag->key);
        $this->assertNull($tag->keyDefaultMethod);
        $this->assertEquals([], $tag->containerIdExclude);
        $this->assertTrue($tag->selfExclude);
    }

    public function testTaggedAsNotDefault(): void
    {
        $tag = new TaggedAs('tags.handler-one', false, 'getPriority', false, 'key', 'getKey', ['id1', 'id2'], false);

        $this->assertFalse($tag->isLazy);
        $this->assertEquals('getPriority', $tag->priorityDefaultMethod);
        $this->assertFalse($tag->useKeys);
        $this->assertEquals('key', $tag->key);
        $this->assertEquals('getKey', $tag->keyDefaultMethod);
        $this->assertEquals(['id1', 'id2'], $tag->containerIdExclude);
        $this->assertFalse($tag->selfExclude);
    }
}
