<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys;

use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Tests\TaggedAsKeys\Fixures\Attributes\One;
use Tests\TaggedAsKeys\Fixures\Attributes\Two;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\Attributes\Tag
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\LazyDefinitionIterator
 * @covers \Kaspi\DiContainer\Traits\TagsTrait
 *
 * @internal
 */
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
        $taggedAs->setContainer($this->container);
        $items = $taggedAs->getServicesTaggedAs();

        $this->assertCount(1, $items);
        $this->assertInstanceOf(One::class, $items['key-service']);
    }

    public function testKeyOverrideLazyPhpDefinition(): void
    {
        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                Fixures\One::class => diAutowire(Fixures\One::class)
                    ->bindTag('tags.one', options: ['key.override' => 'key-service'], priority: 100),
                Fixures\Two::class => diAutowire(Fixures\Two::class)
                    ->bindTag('tags.one', options: ['key.override' => 'key-service'], priority: 0),
            ])
        ;

        $this->container->method('get')
            ->with(Fixures\One::class)
            ->willReturn(new Fixures\One())
        ;

        // get item with highest priority - One::class with priority 100, Two::class with priority 0
        $taggedAs = new DiDefinitionTaggedAs('tags.one', key: 'key.override');
        $taggedAs->setContainer($this->container);
        $items = $taggedAs->getServicesTaggedAs();

        $this->assertCount(1, $items);
        $this->assertInstanceOf(Fixures\One::class, $items->get('key-service'));
    }
}
