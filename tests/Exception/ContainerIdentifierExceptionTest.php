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
        $e = new ContainerIdentifierException(context_id: 100, contect_definition: ['foo' => 'bar']);

        self::assertEquals('Definition identifier must be a non-empty string.', $e->getMessage());
        self::assertEquals(['context_id' => 100, 'contect_definition' => ['foo' => 'bar']], $e->getContext());
    }

    public function testCustomMessage(): void
    {
        $e = new ContainerIdentifierException('Lorem ipsum dolor sit amet.');

        self::assertEquals('Lorem ipsum dolor sit amet.', $e->getMessage());
        self::assertEmpty($e->getContext());
    }
}
