<?php

declare(strict_types=1);

namespace Tests\Exception;

use Kaspi\DiContainer\Exception\DefinitionsLoaderException;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Kaspi\DiContainer\Exception\DefinitionsLoaderException
 *
 * @internal
 */
class DefinitionsLoaderExceptionTest extends TestCase
{
    public function testContextByIndex(): void
    {
        $e = (new DefinitionsLoaderException())
            ->setContext('string', ['foo', 'bar'], new stdClass())
        ;

        $this->assertEquals([0 => 'string', 1 => ['foo', 'bar'], 2 => new stdClass()], $e->getContext());
    }

    public function testContextByNamedArgument(): void
    {
        $e = (new DefinitionsLoaderException())
            ->setContext(context_string: 'string', context_dto: ['foo', 'bar'], context_std_class: new stdClass())
        ;

        $this->assertEquals(['context_string' => 'string', 'context_dto' => ['foo', 'bar'], 'context_std_class' => new stdClass()], $e->getContext());
    }
}
