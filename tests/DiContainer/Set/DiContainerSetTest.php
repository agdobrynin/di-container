<?php

declare(strict_types=1);

namespace Tests\DiContainer\Set;

use Generator;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\ContainerIdentifierException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
#[CoversClass(DiContainer::class)]
#[CoversClass(ContainerIdentifierException::class)]
#[CoversClass(Helper::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(DiDefinitionValue::class)]
class DiContainerSetTest extends TestCase
{
    #[DataProvider('dataProviderWrongIdentifier')]
    public function testWrongIdentifier(string $identifier, mixed $definition): void
    {
        $this->expectException(ContainerIdentifierExceptionInterface::class);

        (new DiContainer())->set($identifier, $definition);
    }

    public static function dataProviderWrongIdentifier(): Generator
    {
        yield 'empty string' => ['', new stdClass()];

        yield 'empty definition and definition without identifier' => ['', new DiDefinitionValue('oooo')];
    }

    #[DataProvider('dataProviderSuccessIdentifier')]
    public function testSuccessIdentifier(string $id, mixed $definition): void
    {
        $container = (new DiContainer())->set($id, $definition);

        $this->assertTrue($container->has($id));
    }

    public static function dataProviderSuccessIdentifier(): Generator
    {
        yield 'with spaces' => [' foo ', 'definition'];

        yield 'with string as "null" and definition NULL' => ['null', null];
    }

    public function testIdentifierNotUnique(): void
    {
        $container = (new DiContainer())->set('key', 'value');

        $this->expectException(ContainerAlreadyRegisteredExceptionInterface::class);

        $container->set('key', 'value2');
    }
}
