<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diValue;

/**
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

    public static function dataProviderEmptyString(): Generator
    {
        yield 'empty string' => [''];

        yield 'string with spaces' => ['  '];
    }

    /**
     * @dataProvider dataProviderEmptyString
     */
    public function testKeyIsEmptyString(string $key): void
    {
        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn([
                'service.oka' => diValue('oka')->bindTag('tags.one', options: ['key' => 'aaa']),
            ])
        ;

        $taggedAs = new DiDefinitionTaggedAs('tags.one', key: $key);
        $taggedAs->setContainer($this->container);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('Argument $key must be non-empty string');

        $taggedAs->getServicesTaggedAs();
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
     * @dataProvider dataProviderInvalidDefinitions
     */
    public function testKeyOptionIsNonEmptyString(DiDefinitionTaggedAs $taggedAs, array $getDefinitions): void
    {
        $this->container->expects(self::once())
            ->method('getDefinitions')
            ->willReturn($getDefinitions)
        ;
        $taggedAs->setContainer($this->container);

        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessage('the value must be non-empty string');

        $taggedAs->getServicesTaggedAs();
    }
}
