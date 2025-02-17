<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys;

use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\TaggedAsKeys\Fixures\One;
use Tests\TaggedAsKeys\Fixures\Two;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diValue;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
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
        $taggedAs->setContainer($this->container);

        $collection = $taggedAs->getServicesTaggedAs();

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
        $taggedAs->setContainer($this->container);

        $collection = $taggedAs->getServicesTaggedAs();

        $this->assertIsIterable($collection);
        $this->assertInstanceOf(One::class, $collection['service.one']);
        $this->assertInstanceOf(One::class, $collection->get('service.one'));
    }

    public function testGetKeyByMethodFailReturn(): void
    {
        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                One::class => diAutowire(One::class)->bindTag(
                    'tags.one',
                    options: ['key.my_key' => 'self::getKeyFail']
                ),
            ])
        ;

        $this->container->method('get')
            ->with(One::class)
            ->willReturn(new One())
        ;

        $taggedAs = new DiDefinitionTaggedAs('tags.one', key: 'key.my_key');
        $taggedAs->setContainer($this->container);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Get key by.+One::getKeyFail().+Return type must be "string"\. Got return type: "stdClass", "array"/');

        $taggedAs->getServicesTaggedAs();
    }

    public function testGetKeyByMethodFailMethodNotExist(): void
    {
        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                One::class => diAutowire(One::class)->bindTag(
                    'tags.one',
                    options: ['key.my_key' => 'self::nonExistMethod']
                ),
            ])
        ;

        $this->container->method('get')
            ->with(One::class)
            ->willReturn(new One())
        ;

        $taggedAs = new DiDefinitionTaggedAs('tags.one', key: 'key.my_key');
        $taggedAs->setContainer($this->container);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/One::nonExistMethod().+method must be exist.+Return type must be "string"/');

        $taggedAs->getServicesTaggedAs();
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

        $this->container->method('get')
            ->with(self::logicalOr(One::class, Two::class))
            ->willReturn(new One(), new Two())
        ;

        $taggedAs = new DiDefinitionTaggedAs('tags.one', keyDefaultMethod: 'getDefaultKey');
        $taggedAs->setContainer($this->container);

        $collection = $taggedAs->getServicesTaggedAs();

        $this->assertIsIterable($collection);
        $this->assertInstanceOf(One::class, $collection[One::class]);
        $this->assertInstanceOf(Two::class, $collection['services.key_default']);
    }

    public function testGetKeyByDefaultMethodWrongReturnType(): void
    {
        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                Two::class => diAutowire(Two::class)->bindTag('tags.one'),
            ])
        ;

        $this->container->method('get')
            ->with(self::logicalOr(Two::class))
            ->willReturn(new Two())
        ;

        $taggedAs = new DiDefinitionTaggedAs('tags.one', keyDefaultMethod: 'getDefaultKeyWrongReturnType');
        $taggedAs->setContainer($this->container);

        $collection = $taggedAs->getServicesTaggedAs();

        $this->assertIsIterable($collection);
        $this->assertEquals(Two::class, $collection->key());
    }
}
