<?php

declare(strict_types=1);

namespace Tests\DiContainer\Constructor;

use Generator;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\Exception\ContainerIdentifierException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\SourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

use function Kaspi\DiContainer\diCallable;

/**
 * @internal
 */
#[CoversClass(DiContainer::class)]
#[CoversClass(ContainerIdentifierException::class)]
#[CoversClass(Helper::class)]
#[CoversClass(SourceDefinitionsMutable::class)]
class DiContainerAddDefinitionThroughConstructorTest extends TestCase
{
    #[DataProvider('dataProviderWrongDefinition')]
    public function testDefinitionWithoutStringIdentifier(iterable $definition): void
    {
        $this->expectException(ContainerIdentifierExceptionInterface::class);
        $this->expectExceptionMessage('Definition identifier must be a non-empty string');

        (new DiContainer($definition))
            ->getDefinitions()
            ->valid()
        ;
    }

    public static function dataProviderWrongDefinition(): Generator
    {
        yield 'digit only' => [[10]];

        yield 'object' => [[20 => new stdClass()]];

        yield 'array' => [[[]]];

        yield 'one string' => [['string']];

        yield 'try pass class implement DiDefinitionInterface' => [[diCallable(static fn () => 'string')]];
    }

    #[DataProvider('dataProviderSuccessIdentifier')]
    public function testDefinitionSuccessIdentifier(iterable $definitions, string $identifier): void
    {
        self::assertTrue((new DiContainer(definitions: $definitions))->has($identifier));
    }

    public static function dataProviderSuccessIdentifier(): Generator
    {
        yield 'string with string' => [
            'definitions' => ['string' => 'foo'],
            'identifier' => 'string',
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
        ];
    }

    public function testDefinitionsAsSourceDefinitionsMutableInterface(): void
    {
        $definitions = new SourceDefinitionsMutable(['service.foo' => null]);

        self::assertTrue((new DiContainer(definitions: $definitions))->has('service.foo'));
    }
}
