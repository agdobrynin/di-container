<?php

declare(strict_types=1);

namespace Tests\DiContainer\RemovedDefinitionIds;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Tests\DiContainer\RemovedDefinitionIds\Fixtures\Bar;
use Tests\DiContainer\RemovedDefinitionIds\Fixtures\Foo;

/**
 * @internal
 */
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
class RemovedDefinitionIdsTest extends TestCase
{
    public function testRemovedDefinitionIds(): void
    {
        $container = new DiContainer(
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: true,
                useAttribute: false,
            ),
            removedDefinitionIds: [Foo::class => true]
        );

        self::assertTrue($container->has(Bar::class));
        self::assertFalse($container->has(Foo::class));
    }

    public function testResolveRemovedDefinition(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $container = new DiContainer(
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: true,
                useAttribute: false,
            ),
            removedDefinitionIds: [Foo::class => true]
        );

        $container->get(Foo::class);
    }
}
