<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\TagsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\TaggedAsKeys\Fixtures\Failed\Foo;
use Tests\TaggedAsKeys\Fixtures\OptionKeyReturnEmptyString;

use function Kaspi\DiContainer\diValue;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diValue')]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionTaggedAs::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(TagsTrait::class)]
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

    #[DataProvider('dataProviderEmptyString')]
    public function testKeyIsEmptyString(string $key): void
    {
        $this->expectException(DiDefinitionException::class);
        $this->expectExceptionMessage('must be non-empty string');

        $this->container->expects(self::once())
            ->method('findTaggedDefinitions')
            ->with('tags.one')
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

    #[DataProvider('dataProviderInvalidDefinitions')]
    public function testKeyOptionIsNonEmptyString(DiDefinitionTaggedAs $taggedAs, array $getDefinitions): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('The value of option name "key_srv" must be non-empty string.');

        $this->container->expects(self::once())
            ->method('findTaggedDefinitions')
            ->with('tags.one')
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

    public function testKeyOptionFromMethod(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot get key for tag "tags.one"');

        $this->container->expects(self::once())
            ->method('findTaggedDefinitions')
            ->with('tags.one')
            ->willReturn([
                OptionKeyReturnEmptyString::class => (new DiDefinitionAutowire(OptionKeyReturnEmptyString::class))
                    ->bindTag('tags.one', options: ['key' => 'self::getKeyEmpty'])
                    ->setContainer($this->container),
            ])
        ;

        (new DiDefinitionTaggedAs('tags.one', key: 'key'))->resolve($this->container);
    }

    /**
     * @param DiDefinitionAutowire[] $getDefinitions
     */
    #[DataProvider('dataProviderKeyFromMethodFailed')]
    public function testKeyFromMethodFailed(DiDefinitionTaggedAs $taggedAs, array $getDefinitions): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot get key for tag "tags.one" via method');

        $this->container->expects(self::once())
            ->method('findTaggedDefinitions')
            ->with('tags.one')
            ->willReturnCallback(function () use ($getDefinitions) {
                foreach ($getDefinitions as $getDefinition) {
                    yield $getDefinition->getIdentifier() => $getDefinition->setContainer($this->container);
                }
            })
        ;

        $taggedAs->resolve($this->container);
    }

    public static function dataProviderKeyFromMethodFailed(): Generator
    {
        yield 'private static method' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key'),
            [
                'service_one' => (new DiDefinitionAutowire(Foo::class))
                    ->bindTag('tags.one', options: ['key' => 'self::getKeyStaticPrivate']),
            ],
        ];

        yield 'protected static method' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key'),
            [
                'service_one' => (new DiDefinitionAutowire(Foo::class))
                    ->bindTag('tags.one', options: ['key' => 'self::getKeyStaticProtected']),
            ],
        ];

        yield 'public none-static method' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key'),
            [
                'service_one' => (new DiDefinitionAutowire(Foo::class))
                    ->bindTag('tags.one', options: ['key' => 'self::getKeyNoneStatic']),
            ],
        ];

        yield 'protected none-static method' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key'),
            [
                'service_one' => (new DiDefinitionAutowire(Foo::class))
                    ->bindTag('tags.one', options: ['key' => 'self::getKeyNoneStaticProtected']),
            ],
        ];

        yield 'private none-static method' => [
            new DiDefinitionTaggedAs('tags.one', key: 'key'),
            [
                'service_one' => (new DiDefinitionAutowire(Foo::class))
                    ->bindTag('tags.one', options: ['key' => 'self::getKeyNoneStaticPrivate']),
            ],
        ];
    }
}
