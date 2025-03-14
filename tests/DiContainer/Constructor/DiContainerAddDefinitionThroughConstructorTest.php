<?php

declare(strict_types=1);

namespace Tests\DiContainer\Constructor;

use Generator;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

use function Kaspi\DiContainer\diCallable;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 *
 * @internal
 */
class DiContainerAddDefinitionThroughConstructorTest extends TestCase
{
    public function dataProviderWrongDefinition(): Generator
    {
        yield 'digit only' => [[10]];

        yield 'object' => [[new stdClass()]];

        yield 'array' => [[[]]];

        yield 'try pass class implement DiDefinitionInterface' => [[diCallable(static fn () => 'string')]];
    }

    /**
     * @dataProvider dataProviderWrongDefinition
     */
    public function testDefinitionWithoutStringIdentifier(iterable $definition): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        new DiContainer($definition);
    }

    public function dataProviderSuccessIdentifier(): Generator
    {
        yield 'string with string' => [
            'definitions' => ['string' => 'foo'],
            'identifier' => 'string',
            'definition' => 'foo',
        ];

        yield 'string only' => [
            'definitions' => ['string'],
            'identifier' => 'string',
            'definition' => 'string',
        ];

        $class = new class implements DiDefinitionIdentifierInterface {
            public function getIdentifier(): string
            {
                return 'my.identifier';
            }
        };

        yield 'pass class implement DiDefinitionIdentifierInterface' => [
            'definitions' => [$class],
            'identifier' => 'my.identifier',
            'definition' => $class,
        ];
    }

    /**
     * @dataProvider dataProviderSuccessIdentifier
     */
    public function testDefinitionSuccessIdentifier(iterable $definitions, string $identifier, mixed $definition): void
    {
        $mock = $this->createMock(DiContainer::class);
        $mock->expects($this->once())->method('set')->with($identifier, $definition);

        $mock->__construct($definitions);
    }
}
