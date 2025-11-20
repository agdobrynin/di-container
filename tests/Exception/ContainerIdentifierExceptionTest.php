<?php

declare(strict_types=1);

namespace Tests\Exception;

use Kaspi\DiContainer\Exception\ContainerIdentifierException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Exception\ContainerIdentifierException
 *
 * @internal
 */
class ContainerIdentifierExceptionTest extends TestCase
{
    public function testDefaultMessage(): void
    {
        $e = new ContainerIdentifierException(100, ['foo' => 'bar']);

        self::assertEquals('Definition identifier must be a non-empty string.', $e->getMessage());
        self::assertSame(100, $e->getIdentifier());
        self::assertSame(['foo' => 'bar'], $e->getDefinition());
    }

    public function testCustomMessage(): void
    {
        $e = new ContainerIdentifierException(100, ['foo' => 'bar'], 'Lorem ipsum dolor sit amet.');

        self::assertEquals('Lorem ipsum dolor sit amet.', $e->getMessage());
    }
}
