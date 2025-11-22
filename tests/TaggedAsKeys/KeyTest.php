<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys;

use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\TaggedAsKeys\Fixtures\One;
use Tests\TaggedAsKeys\Fixtures\Two;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diTaggedAs;
use function Kaspi\DiContainer\diValue;

/**
 * @covers \Kaspi\DiContainer\Attributes\Tag
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\diValue
 * @covers \Kaspi\DiContainer\LazyDefinitionIterator
 * @covers \Kaspi\DiContainer\Traits\TagsTrait
 *
 * @internal
 */
class KeyTest extends TestCase
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

    public function testGetKeyAsString(): void
    {
        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                'service.oka' => diValue('oka')->bindTag('tags.one', options: ['key.my_key' => 'aaa']),
            ])
        ;
        $this->container->method('get')
            ->with('service.oka')
            ->willReturn('oka')
        ;

        $taggedAs = new DiDefinitionTaggedAs('tags.one', key: 'key.my_key');

        $collection = $taggedAs->resolve($this->container);

        $this->assertIsIterable($collection);
        $this->assertEquals('aaa', $collection->key());
        $this->assertEquals('oka', $collection->get('aaa'));
        $this->assertEquals('oka', $collection['aaa']);
    }

    public function testGetKeyByMethod(): void
    {
        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                One::class => diAutowire(One::class)->bindTag(
                    'tags.one',
                    options: ['key.my_key' => 'self::getKey']
                ),
            ])
        ;

        $this->container->method('get')
            ->with(One::class)
            ->willReturn(new One())
        ;

        $taggedAs = new DiDefinitionTaggedAs('tags.one', key: 'key.my_key');

        $collection = $taggedAs->resolve($this->container);

        $this->assertIsIterable($collection);
        $this->assertInstanceOf(One::class, $collection['service.one']);
        $this->assertInstanceOf(One::class, $collection->get('service.one'));
    }

    public function testGetKeyByMethodFailReturn(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                One::class => diAutowire(One::class)->bindTag(
                    'tags.one',
                    options: ['key.my_key' => 'self::getKeyFail']
                ),
            ])
        ;

        (new DiDefinitionTaggedAs('tags.one', key: 'key.my_key'))->resolve($this->container);
    }

    public function testGetKeyByMethodFailMethodNotExist(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                One::class => diAutowire(One::class)->bindTag(
                    'tags.one',
                    options: ['key.my_key' => 'self::nonExistMethod']
                ),
            ])
        ;

        (new DiDefinitionTaggedAs('tags.one', key: 'key.my_key'))->resolve($this->container);
    }

    public function testGetKeyByDefaultMethodSuccess(): void
    {
        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                One::class => diAutowire(One::class)->bindTag('tags.one'),
                Two::class => diAutowire(Two::class)->bindTag('tags.one'),
            ])
        ;

        $taggedAs = new DiDefinitionTaggedAs('tags.one', keyDefaultMethod: 'getDefaultKey');

        $collection = $taggedAs->resolve($this->container);

        $this->assertIsIterable($collection);
        $this->assertEquals(One::class, $collection->key());
        $collection->next();
        $this->assertEquals('services.key_default', $collection->key());
    }

    public function testGetKeyByDefaultMethodWrongReturnType(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                Two::class => diAutowire(Two::class)->bindTag('tags.one'),
            ])
        ;

        (new DiDefinitionTaggedAs('tags.one', keyDefaultMethod: 'getDefaultKeyWrongReturnType'))
            ->resolve($this->container)
        ;
    }

    public function testGetKeyCollectionWithPhpAttribute(): void
    {
        $this->container->method('getConfig')
            ->willReturn(new DiContainerConfig())
        ;

        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                Fixtures\Attributes\One::class => diAutowire(Fixtures\Attributes\One::class),
                Fixtures\Attributes\Three::class => diAutowire(Fixtures\Attributes\Three::class),
                Fixtures\Attributes\Two::class => diAutowire(Fixtures\Attributes\Two::class),
            ])
        ;

        // $useKeys=false override by $key, collection has keys.
        $taggedAs = diTaggedAs(tag: 'tags.some-service', useKeys: false, key: 'key');

        $collection = $taggedAs->resolve($this->container);

        $this->assertCount(2, $collection);
        // test use DiContainer mock and not resolve real class. Test keys.
        $this->assertTrue($collection->valid());
        $this->assertEquals('some_service.two', $collection->key()); // priority = 10
        $collection->next();
        $this->assertEquals('some_service.one', $collection->key()); // priority = 1
    }

    public function testGetKeyCollectionByMethodWithPhpAttribute(): void
    {
        $this->container->method('getConfig')
            ->willReturn(new DiContainerConfig())
        ;

        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                Fixtures\Attributes\One::class => diAutowire(Fixtures\Attributes\One::class),
                Fixtures\Attributes\Three::class => diAutowire(Fixtures\Attributes\Three::class),
                Fixtures\Attributes\Two::class => diAutowire(Fixtures\Attributes\Two::class),
            ])
        ;

        // $useKeys=false override by $key, collection has keys.
        $taggedAs = diTaggedAs(tag: 'tags.some-service', useKeys: false, key: 'key.method');

        $collection = $taggedAs->resolve($this->container);

        $this->assertCount(2, $collection);
        // test use DiContainer mock and not resolve real class. Test keys.
        $this->assertTrue($collection->valid());
        $this->assertEquals('some_service.Dos', $collection->key()); // priority = 10
        $collection->next();
        $this->assertEquals('some_service.Uno', $collection->key()); // priority = 1
    }
}
