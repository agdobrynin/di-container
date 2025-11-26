<?php

declare(strict_types=1);

namespace Tests\DiContainer\Set;

use Generator;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\Exception\ContainerIdentifierException
 * @covers \Kaspi\DiContainer\Helper
 *
 * @internal
 */
class DiContainerSetTest extends TestCase
{
    /**
     * @dataProvider dataProviderWrongIdentifier
     */
    public function testWrongIdentifier(string $identifier, mixed $definition): void
    {
        $this->expectException(ContainerIdentifierExceptionInterface::class);

        (new DiContainer())->set($identifier, $definition);
    }

    public function dataProviderWrongIdentifier(): Generator
    {
        yield 'empty string' => ['', new stdClass()];

        yield 'empty definition and definition without identifier' => ['', new DiDefinitionValue('oooo')];
    }

    /**
     * @dataProvider dataProviderSuccessIdentifier
     */
    public function testSuccessIdentifier(string $id, mixed $definition): void
    {
        $container = (new DiContainer())->set($id, $definition);

        $this->assertTrue($container->has($id));
    }

    public function dataProviderSuccessIdentifier(): Generator
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
