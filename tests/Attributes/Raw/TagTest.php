<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

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
    public static function dataProviderFail(): \Generator
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

    public function testTagOptionsDefault(): void
    {
        $tag = new Tag('tags.handler-one');

        $this->assertEquals('tags.handler-one', $tag->getIdentifier());
        $this->assertEquals([], $tag->getOptions());
        $this->assertNull($tag->getPriority());
        $this->assertNull($tag->getPriorityMethod());
    }

    public function testTagOptions(): void
    {
        $tag = new Tag('tags.handler-one', ['priority' => 100, 'meta-data' => ['foo' => 'bar']]);

        $this->assertEquals(['priority' => 100, 'meta-data' => ['foo' => 'bar']], $tag->getOptions());
    }

    public function testConstructorParamsOverrideOptionPriorityAndPriorityMethod(): void
    {
        $tag = new Tag(
            'tags.handler-one',
            ['priority' => 100, 'priorityMethod' => ['foo' => 'bar']],
            1000,
            'getPriority'
        );

        $this->assertEquals([
            'priority' => 100,
            'priorityMethod' => ['foo' => 'bar'],
        ], $tag->getOptions());

        $this->assertEquals(1000, $tag->getPriority());
        $this->assertEquals('getPriority', $tag->getPriorityMethod());
    }
}
