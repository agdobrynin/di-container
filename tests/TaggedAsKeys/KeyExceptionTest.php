<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\TaggedAsKeys\Fixtures\Failed\Foo;
use Tests\TaggedAsKeys\Fixtures\OptionKeyReturnEmptyString;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diValue;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diValue
 * @covers \Kaspi\DiContainer\Traits\TagsTrait
 *
 * @internal
 */
class KeyExceptionTest extends TestCase
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

    /**
     * @dataProvider dataProviderEmptyString
     */
    public function testKeyIsEmptyString(string $key): void
    {
        $this->expectException(DiDefinitionException::class);

        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                'service.oka' => diValue('oka')->bindTag('tags.one', options: ['key' => 'aaa']),
            ])
        ;

        (new DiDefinitionTaggedAs('tags.one', key: $key))
            ->resolve($this->container)
        ;
    }

    public static function dataProviderEmptyString(): Generator
    {
        yield 'empty string' => [''];

        yield 'string with spaces' => ['  '];
    }

    /**
     * @dataProvider dataProviderInvalidDefinitions
     */
    public function testKeyOptionIsNonEmptyString(DiDefinitionTaggedAs $taggedAs, array $getDefinitions): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('The value of option name "key_srv" must be non-empty string.');

        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn($getDefinitions)
        ;

        $taggedAs->resolve($this->container);
    }

    public static function dataProviderInvalidDefinitions(): Generator
    {
        yield 'key option is none-string' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key_srv'),
            [
                'srv.one' => diValue('oka')->bindTag('tags.one', options: ['key_srv' => ['sub' => 'aaa']]),
            ],
        ];

        yield 'key option is empty string' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key_srv'),
            [
                'srv.one' => diValue('oka')->bindTag('tags.one', options: ['key_srv' => '']),
            ],
        ];

        yield 'key option is string with spaces' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key_srv'),
            [
                'srv.one' => diValue('oka')->bindTag('tags.one', options: ['key_srv' => '   ']),
            ],
        ];
    }

    /**
     * @dataProvider dataProviderKeyOptionFromMethod
     */
    public function testKeyOptionFromMethod(DiDefinitionTaggedAs $taggedAs, array $getDefinitions): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn($getDefinitions)
        ;

        $taggedAs->resolve($this->container);
    }

    public function dataProviderKeyOptionFromMethod(): Generator
    {
        yield 'empty string' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key'),
            [
                'service_one' => diAutowire(OptionKeyReturnEmptyString::class)
                    ->bindTag('tags.one', options: ['key' => 'self::getKeyEmpty']),
            ],
        ];

        yield 'string with spaces' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key'),
            [
                'service_one' => diAutowire(OptionKeyReturnEmptyString::class)
                    ->bindTag('tags.one', options: ['key' => 'self::getKeySpaces']),
            ],
        ];
    }

    /**
     * @dataProvider dataProviderKeyFromMethodFailed
     */
    public function testKeyFromMethodFailed(DiDefinitionTaggedAs $taggedAs, array $getDefinitions): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);

        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn($getDefinitions)
        ;

        $taggedAs->resolve($this->container);
    }

    public function dataProviderKeyFromMethodFailed(): Generator
    {
        yield 'private static method' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key'),
            [
                'service_one' => diAutowire(Foo::class)
                    ->bindTag('tags.one', options: ['key' => 'self::getKeyStaticPrivate']),
            ],
        ];

        yield 'protected static method' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key'),
            [
                'service_one' => diAutowire(Foo::class)
                    ->bindTag('tags.one', options: ['key' => 'self::getKeyStaticProtected']),
            ],
        ];

        yield 'public none-static method' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key'),
            [
                'service_one' => diAutowire(Foo::class)
                    ->bindTag('tags.one', options: ['key' => 'self::getKeyNoneStatic']),
            ],
        ];

        yield 'protected none-static method' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key'),
            [
                'service_one' => diAutowire(Foo::class)
                    ->bindTag('tags.one', options: ['key' => 'self::getKeyNoneStaticProtected']),
            ],
        ];

        yield 'private none-static method' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key'),
            [
                'service_one' => diAutowire(Foo::class)
                    ->bindTag('tags.one', options: ['key' => 'self::getKeyNoneStaticPrivate']),
            ],
        ];
    }
}
