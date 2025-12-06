<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\LazyDefinitionIterator;
use Kaspi\DiContainer\Traits\TagsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\TaggedAsKeys\Fixtures\Attributes\One;
use Tests\TaggedAsKeys\Fixtures\Attributes\Two;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(Tag::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionTaggedAs::class)]
#[CoversClass(LazyDefinitionIterator::class)]
#[CoversClass(TagsTrait::class)]
class KeyOverrideTest extends TestCase
{
    private ?object $container;

    public function setUp(): void
    {
        $this->container = $this->createMock(DiContainerInterface::class);
    }

    public function tearDown(): void
    {
        $this->container = null;
    }

    public function testKeyOverrideLazyPhpAttribute(): void
    {
        $this->container->expects(self::once())
            ->method('findTaggedDefinitions')
            ->with('tags.one')
            ->willReturn([
                One::class => (new DiDefinitionAutowire(One::class))
                    ->setContainer($this->container), // #[Tag('tags.one', options: ['key.override' => 'key-service'], priority: 100)]
                Two::class => (new DiDefinitionAutowire(Two::class))
                    ->setContainer($this->container), // #[Tag('tags.one', options: ['key.override' => 'key-service'], priority: 0)]
            ])
        ;

        $this->container->method('getConfig')
            ->willReturn(new DiContainerConfig())
        ;

        $this->container->method('get')
            ->with(One::class)
            ->willReturn(new One())
        ;

        // get item with highest priority - One::class with priority 100, Two::class with priority 0
        $taggedAs = new DiDefinitionTaggedAs('tags.one', key: 'key.override');

        $items = $taggedAs->resolve($this->container);

        $this->assertCount(1, $items);
        $this->assertInstanceOf(One::class, $items['key-service']);
    }

    public function testKeyOverrideLazyPhpDefinition(): void
    {
        $this->container->expects(self::once())
            ->method('findTaggedDefinitions')
            ->with('tags.one')
            ->willReturn([
                Fixtures\One::class => (new DiDefinitionAutowire(Fixtures\One::class))
                    ->setContainer($this->container)
                    ->bindTag('tags.one', options: ['key.override' => 'key-service'], priority: 100),
                Fixtures\Two::class => (new DiDefinitionAutowire(Fixtures\Two::class))
                    ->setContainer($this->container)
                    ->bindTag('tags.one', options: ['key.override' => 'key-service'], priority: 0),
            ])
        ;

        $this->container->method('get')
            ->with(Fixtures\One::class)
            ->willReturn(new Fixtures\One())
        ;

        // get item with highest priority - One::class with priority 100, Two::class with priority 0
        $taggedAs = new DiDefinitionTaggedAs('tags.one', key: 'key.override');
        $items = $taggedAs->resolve($this->container);

        $this->assertCount(1, $items);
        $this->assertInstanceOf(Fixtures\One::class, $items->get('key-service'));
    }
}
