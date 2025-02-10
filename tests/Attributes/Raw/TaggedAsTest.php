<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

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

        new TaggedAs($name);
    }

    public function testTaggedAsDefault(): void
    {
        $tag = new TaggedAs('tags.handler-one');

        $this->assertEquals('tags.handler-one', $tag->getIdentifier());
        $this->assertTrue($tag->isLazy());
        $this->assertNull($tag->getPriorityDefaultMethod());
    }

    public function testTaggedAsLazyAndDefaultPriorityMethod(): void
    {
        $tag = new TaggedAs('tags.handler-one', false, 'getPriority');

        $this->assertFalse($tag->isLazy());
        $this->assertEquals('getPriority', $tag->getPriorityDefaultMethod());
    }
}
