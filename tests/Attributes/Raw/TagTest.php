<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Generator;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Tag::class)]
class TagTest extends TestCase
{
    #[DataProvider('dataProviderFail')]
    public function testTagFail(string $name): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('The $name parameter must be a non-empty string.');

        new Tag($name);
    }

    public static function dataProviderFail(): Generator
    {
        yield 'empty string' => [''];

        yield 'string with spaces' => ['   '];
    }

    public function testTagDefault(): void
    {
        $tag = new Tag('tags.handler-one');

        $this->assertEquals('tags.handler-one', $tag->getIdentifier());
        $this->assertEquals([], $tag->getOptions());
        $this->assertNull($tag->getPriority());
        $this->assertNull($tag->getPriorityMethod());
    }

    public function testTagOptionsNotOverrideSomeOptions(): void
    {
        $tag = new Tag('tags.handler-one', ['priority' => 100, 'meta-data' => ['foo' => 'bar']], 50, 'getPriorityTag');

        $this->assertEquals(['priority' => 100, 'meta-data' => ['foo' => 'bar']], $tag->getOptions());
        $this->assertEquals(50, $tag->getPriority());
        $this->assertEquals('getPriorityTag', $tag->getPriorityMethod());
    }
}
