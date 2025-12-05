<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\LazyDefinitionIterator;
use Kaspi\DiContainer\Traits\TagsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\TaggedAsKeys\Fixtures\Attributes\One;
use Tests\TaggedAsKeys\Fixtures\Attributes\Two;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(\Kaspi\DiContainer\Attributes\Tag::class)]
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
            ->method('getDefinitions')
            ->willReturn([
                One::class => diAutowire(One::class), // #[Tag('tags.one', options: ['key.override' => 'key-service'], priority: 100)]
                Two::class => diAutowire(Two::class), // #[Tag('tags.one', options: ['key.override' => 'key-service'], priority: 0)]
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
            ->method('getDefinitions')
            ->willReturn([
                Fixtures\One::class => diAutowire(Fixtures\One::class)
                    ->bindTag('tags.one', options: ['key.override' => 'key-service'], priority: 100),
                Fixtures\Two::class => diAutowire(Fixtures\Two::class)
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
