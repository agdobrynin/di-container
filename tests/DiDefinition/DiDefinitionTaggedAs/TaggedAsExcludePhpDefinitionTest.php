<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs;

use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Exclude\Definition\One;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Exclude\Definition\TaggedAsCollection;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Exclude\Definition\Three;
use Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Exclude\Definition\Two;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\LazyDefinitionIterator
 *
 * @internal
 */
class TaggedAsExcludePhpDefinitionTest extends TestCase
{
    private ?DiContainerInterface $container = null;

    public function setUp(): void
    {
        $this->container = $this->createMock(DiContainerInterface::class);
        $this->container->method('getDefinitions')
            ->willReturn([
                One::class => diAutowire(One::class)
                    ->bindTag('tags.aaa'),
                Two::class => diAutowire(Two::class)
                    ->bindTag('tags.aaa'),
                Three::class => diAutowire(Three::class)
                    ->bindTag('tags.aaa'),
                TaggedAsCollection::class => diAutowire(TaggedAsCollection::class)
                    ->bindTag('tags.aaa'),
            ])
        ;
        $this->container->method('getConfig')
            ->willReturn(new DiContainerConfig(useAttribute: false))
        ;
    }

    public function tearDown(): void
    {
        $this->container = null;
    }

    public function testTaggedAsExcludeSelfTrue(): void
    {
        $taggedAs = (new DiDefinitionTaggedAs('tags.aaa'))
            ->setCallingByService(diAutowire(TaggedAsCollection::class))
        ;
        $taggedAs->setContainer($this->container);

        $collection = $taggedAs->getServicesTaggedAs();

        $this->assertCount(3, $collection);
        $this->assertTrue($collection->has(One::class));
        $this->assertTrue($collection->has(Two::class));
        $this->assertTrue($collection->has(Three::class));
        $this->assertFalse($collection->has(TaggedAsCollection::class));
    }

    public function testTaggedAsExcludeSelfFalse(): void
    {
        $taggedAs = (new DiDefinitionTaggedAs('tags.aaa', selfExclude: false))
            ->setCallingByService(diAutowire(TaggedAsCollection::class))
        ;
        $taggedAs->setContainer($this->container);

        $collection = $taggedAs->getServicesTaggedAs();

        $this->assertCount(4, $collection);
        $this->assertTrue($collection->has(One::class));
        $this->assertTrue($collection->has(Two::class));
        $this->assertTrue($collection->has(Three::class));
        $this->assertTrue($collection->has(TaggedAsCollection::class));
    }

    public function testTaggedAsExcludeSelfTrueAndExcludeIds(): void
    {
        $taggedAs = (new DiDefinitionTaggedAs('tags.aaa', containerIdExcludes: [One::class, Three::class]))
            ->setCallingByService(diAutowire(TaggedAsCollection::class))
        ;
        $taggedAs->setContainer($this->container);

        $collection = $taggedAs->getServicesTaggedAs();

        $this->assertCount(1, $collection);
        $this->assertFalse($collection->has(One::class));
        $this->assertTrue($collection->has(Two::class));
        $this->assertFalse($collection->has(Three::class));
        $this->assertFalse($collection->has(TaggedAsCollection::class));
    }
}
