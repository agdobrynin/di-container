<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable;

use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionCallable::class)]
class ResetTest extends TestCase
{
    public function testReset(): void
    {
        $container = $this->createMock(DiContainerInterface::class);
        $definition = new DiDefinitionCallable(static fn (string $name) => $name.'!');
        $definition->bindArguments('foo');

        self::assertEquals('foo!', $definition->resolve($container));

        $definition->reset();

        self::assertEquals('foo!', $definition->resolve($container));
    }
}
