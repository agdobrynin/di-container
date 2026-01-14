<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\LazyDefinitionIterator;
use Kaspi\DiContainer\SourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diValue;

/**
 * @internal
 */
#[CoversFunction('Kaspi\DiContainer\diValue')]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiDefinitionTaggedAs::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(Helper::class)]
#[CoversClass(LazyDefinitionIterator::class)]
#[CoversClass(SourceDefinitionsMutable::class)]
class TaggedAsTest extends TestCase
{
    public function testTaggedAsEmptyLazy(): void
    {
        $taggedAs = (new DiDefinitionTaggedAs('tags.empty', true));
        $container = new DiContainer([
            's1' => diValue(['ok']),
            's2' => diValue(['ok-ko']),
        ]);

        $this->assertEquals('tags.empty', $taggedAs->getDefinition());
        // return generator because definition marked as lazy.
        $this->assertFalse($taggedAs->resolve($container)->valid());
    }

    public function testTaggedAsEmptyNotLazy(): void
    {
        $taggedAs = (new DiDefinitionTaggedAs('tags.empty', false));
        $container = new DiContainer([
            's1' => diValue(['ok']),
            's2' => diValue(['ok-ko']),
        ]);

        $this->assertIsArray($taggedAs->resolve($container));
        $this->assertCount(0, $taggedAs->resolve($container));
    }

    public function testTaggedAsWithTagsOptions(): void
    {
        /** @var DiDefinitionValue $taggedAs */
        $taggedAs = diValue('services.one')
            ->bindTag('tags.system.voters', priority: 0)
            ->bindTag('services.one', ['priority' => 100, 'meta-data' => ['1', '2']])
        ;

        $this->assertEquals(
            [
                'tags.system.voters' => ['priority' => 0],
                'services.one' => ['priority' => 100, 'meta-data' => ['1', '2']],
            ],
            $taggedAs->getTags()
        );

        $this->assertEquals(['priority' => 0], $taggedAs->getTag('tags.system.voters'));
        $this->assertEquals(0, $taggedAs->geTagPriority('tags.system.voters'));

        $this->assertEquals(100, $taggedAs->geTagPriority('services.one'));

        $this->assertNull($taggedAs->getTag('tags.non-existent-tag'));
        $this->assertNull($taggedAs->geTagPriority('tags.non-existent-tag'));
    }
}
