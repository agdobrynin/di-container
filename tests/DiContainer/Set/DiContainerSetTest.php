<?php

declare(strict_types=1);

namespace Tests\DiContainer\Set;

use Generator;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 *
 * @internal
 */
class DiContainerSetTest extends TestCase
{
    public function dataProviderWrongIdentifier(): Generator
    {
        yield 'empty string' => [''];

        yield 'spaces' => ['   '];
    }

    /**
     * @dataProvider dataProviderWrongIdentifier
     */
    public function testWrongIdentifier(string $id): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        (new DiContainer())->set($id, 'foo');
    }

    public function dataProviderSuccessIdentifier(): Generator
    {
        yield 'with spaces' => [' foo ', 'definition'];

        yield 'with string as "null" and definition NULL' => ['null', null];
    }

    /**
     * @dataProvider dataProviderSuccessIdentifier
     */
    public function testSuccessIdentifier(string $id, mixed $definition): void
    {
        $container = (new DiContainer())->set($id, $definition);

        $this->assertTrue($container->has($id));
    }

    public function testIdentifierNotUnique(): void
    {
        $container = (new DiContainer())->set('key', 'value');

        $this->expectException(ContainerAlreadyRegisteredExceptionInterface::class);

        $container->set('key', 'value2');
    }
}
