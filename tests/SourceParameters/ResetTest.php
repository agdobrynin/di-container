<?php

declare(strict_types=1);

namespace Tests\SourceParameters;

use Generator;
use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;
use Kaspi\DiContainer\Parameters\AbstractSourceParameters;
use Kaspi\DiContainer\Parameters\DeferredSourceParameters;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Kaspi\DiContainer\Parameters\SourceParameterItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_keys;

/**
 * @internal
 */
#[CoversClass(AbstractSourceParameters::class)]
#[CoversClass(DeferredSourceParameters::class)]
#[CoversClass(ImmediateSourceParameters::class)]
#[CoversClass(SourceParameterItem::class)]
class ResetTest extends TestCase
{
    #[DataProvider('dataProvider')]
    public function testResetImmediate(SourceParametersMutableInterface $params): void
    {
        self::assertTrue($params->has('foo'));
        self::assertEquals('bar', $params->get('foo'));

        self::assertTrue($params->has('baz'));
        self::assertEquals('qux', $params->get('baz'));

        $params->set('qux', '{foo}|{baz}');
        self::assertEquals('bar|qux', $params->get('qux'));

        $names = array_keys([...$params->parameters()]);
        self::assertEquals(['foo', 'baz', 'qux'], $names);

        $params->reset();

        $names = array_keys([...$params->parameters()]);
        self::assertEquals(['foo', 'baz'], $names);
    }

    public static function dataProvider(): Generator
    {
        $srcArray = [
            'foo' => 'bar',
            'baz' => 'qux',
        ];

        $srcGenerator = static function (): Generator {
            yield 'foo' => 'bar';

            yield 'baz' => 'qux';
        };

        yield 'immediate from array' => [
            new ImmediateSourceParameters($srcArray),
        ];

        yield 'immediate from generator' => [
            new ImmediateSourceParameters($srcGenerator()),
        ];

        yield 'deferred from generator' => [
            new DeferredSourceParameters($srcGenerator),
        ];

        yield 'deferred from callable' => [
            new DeferredSourceParameters([new ParametersSrc($srcArray), 'getParameters']),
        ];
    }
}

final class ParametersSrc
{
    public function __construct(private readonly array $parameters) {}

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
