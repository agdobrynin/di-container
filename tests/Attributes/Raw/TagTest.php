<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Generator;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Attributes\Tag
 *
 * @internal
 */
class TagTest extends TestCase
{
    public static function dataProviderFail(): Generator
    {
        yield 'empty string' => [''];

        yield 'string with spaces' => ['   '];
    }

    /**
     * @dataProvider dataProviderFail
     */
    public function testTagFail(string $name): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('parameter must be a non-empty string');

        new Tag($name);
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
