<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys;

use ArrayAccess;
use Countable;
use Iterator;
use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\TaggedAsKeys\Fixtures\One;
use Tests\TaggedAsKeys\Fixtures\TaggedService;
use Tests\TaggedAsKeys\Fixtures\Three;
use Tests\TaggedAsKeys\Fixtures\Two;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\LazyDefinitionIterator
 *
 * @internal
 */
class KeyThroughContainerAsPhpDefinitionTest extends TestCase
{
    public function testNotLazyKeyAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(TaggedService::class)
                ->bindArguments(items: diTaggedAs('tags.one', isLazy: false, key: 'key')),
            diAutowire(One::class)
                ->bindTag('tags.one', options: ['key' => 'serv-one'], priority: 0),
            diAutowire(Two::class)
                ->bindTag('tags.one', options: ['key' => 'serv-two'], priority: 100),
            diAutowire(Three::class)
                ->bindTag('tags.one', options: ['key' => 'serv-three'], priority: 10),
        ]);

        $class = $container->get(TaggedService::class);

        $this->assertIsArray($class->items);
        $this->assertCount(3, $class->items);
        $this->assertInstanceOf(One::class, $class->items['serv-one']);
        $this->assertInstanceOf(Two::class, $class->items['serv-two']);
        $this->assertInstanceOf(Three::class, $class->items['serv-three']);
    }

    public function testLazyKeyAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(TaggedService::class)
                ->bindArguments(items: diTaggedAs('tags.one', key: 'key')),
            diAutowire(One::class)
                ->bindTag('tags.one', options: ['key' => 'serv-one'], priority: 0),
            diAutowire(Two::class)
                ->bindTag('tags.one', options: ['key' => 'serv-two'], priority: 100),
            diAutowire(Three::class)
                ->bindTag('tags.one', options: ['key' => 'serv-three'], priority: 10),
        ]);

        $class = $container->get(TaggedService::class);

        $this->assertIsIterable($class->items);
        $this->assertInstanceOf(ContainerInterface::class, $class->items);
        $this->assertInstanceOf(Countable::class, $class->items);
        $this->assertInstanceOf(ArrayAccess::class, $class->items);
        $this->assertInstanceOf(Iterator::class, $class->items);

        $this->assertCount(3, $class->items);
        $this->assertEquals(3, $class->items->count());

        $this->assertInstanceOf(One::class, $class->items['serv-one']);
        $this->assertInstanceOf(One::class, $class->items->get('serv-one'));
        $this->assertTrue($class->items->has('serv-one'));

        $this->assertInstanceOf(Two::class, $class->items['serv-two']);
        $this->assertInstanceOf(Two::class, $class->items->get('serv-two'));
        $this->assertTrue($class->items->has('serv-two'));

        $this->assertInstanceOf(Three::class, $class->items['serv-three']);
        $this->assertInstanceOf(Three::class, $class->items->get('serv-three'));
        $this->assertTrue($class->items->has('serv-three'));
    }

    public function testLazyKeyAsMethod(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(TaggedService::class)
                ->bindArguments(items: diTaggedAs('tags.one', key: 'key', keyDefaultMethod: 'getDefaultKey')),
            diAutowire(One::class)
                ->bindTag('tags.one', options: ['key' => 'self::getKey']),
            diAutowire(Two::class)
                ->bindTag('tags.one'),
            diAutowire(Three::class)
                ->bindTag('tags.one', options: ['key' => 'self::getKey']),
        ]);

        $class = $container->get(TaggedService::class);

        $this->assertCount(3, $class->items);

        $this->assertInstanceOf(One::class, $class->items['service.one']);
        $this->assertInstanceOf(One::class, $class->items->get('service.one'));

        $this->assertInstanceOf(Two::class, $class->items['services.key_default']);
        $this->assertInstanceOf(Two::class, $class->items->get('services.key_default'));

        $this->assertInstanceOf(Three::class, $class->items['service.three.method']);
        $this->assertInstanceOf(Three::class, $class->items->get('service.three.method'));
    }
}
