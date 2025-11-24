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
    public function testContextAsNamedArgument(): void
    {
        $e = (new ContainerIdentifierException())
            ->setContext(context_id: 100, context_definition: ['foo' => 'bar'])
        ;

        self::assertEquals(['context_id' => 100, 'context_definition' => ['foo' => 'bar']], $e->getContext());
    }

    public function testContextAsIndexesArgument(): void
    {
        $e = (new ContainerIdentifierException())
            ->setContext(100, ['foo' => 'bar'])
        ;

        self::assertEquals([0 => 100, 1 => ['foo' => 'bar']], $e->getContext());
    }
}
