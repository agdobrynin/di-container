<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\Attributes\Tag
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\LazyDefinitionIterator
 *
 * @internal
 */
class KeyThroughContainerAsPhpAttributesTest extends TestCase
{
    public function testNotLazyKeyAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(Fixures\Attributes\One::class),
            diAutowire(Fixures\Attributes\Two::class),
            diAutowire(Fixures\Attributes\Three::class),
        ]);

        // 'tags.one'
        $class = $container->get(Fixures\Attributes\TaggedServiceAsArray::class);

        $this->assertIsArray($class->items);
        $this->assertCount(2, $class->items);

        $this->assertInstanceOf(Fixures\Attributes\One::class, $class->items['some_service.one-other']);
        $this->assertInstanceOf(Fixures\Attributes\Two::class, $class->items['some_service.Dos']);
    }

    public function testLazyKeyAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(Fixures\Attributes\One::class),
            diAutowire(Fixures\Attributes\Two::class),
            diAutowire(Fixures\Attributes\Three::class),
        ]);

        // 'tags.one'
        $class = $container->get(Fixures\Attributes\TaggedServiceAsLazy::class);

        $this->assertIsIterable($class->items);
        $this->assertCount(2, $class->items);
        $this->assertEquals(2, $class->items->count());

        $this->assertInstanceOf(Fixures\Attributes\One::class, $class->items['some_service.one-other']);
        $this->assertInstanceOf(Fixures\Attributes\One::class, $class->items->get('some_service.one-other'));

        $this->assertInstanceOf(Fixures\Attributes\Two::class, $class->items['some_service.Dos']);
        $this->assertInstanceOf(Fixures\Attributes\Two::class, $class->items->get('some_service.Dos'));
    }

    public function testLazyGetKeyByMethod(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(Fixures\Attributes\One::class),
            diAutowire(Fixures\Attributes\Two::class),
            diAutowire(Fixures\Attributes\Three::class),
        ]);

        $res = $container->call([Fixures\Attributes\TaggedServiceAsLazy::class, 'getKeyByMethod']);

        $this->assertInstanceOf(Fixures\Attributes\Three::class, $res->get('signed_service'));
    }
}
