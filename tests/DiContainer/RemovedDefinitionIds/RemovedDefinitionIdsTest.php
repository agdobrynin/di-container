<?php

declare(strict_types=1);

namespace Tests\DiContainer\RemovedDefinitionIds;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\DeferredSourceDefinitionsMutable;
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
#[CoversClass(DeferredSourceDefinitionsMutable::class)]
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
        self::assertSame(
            [Foo::class => true],
            [...$container->getRemovedDefinitionIds()]
        );
    }

    public function testDeferredRemovedDefinitionIds(): void
    {
        $container = new DiContainer(
            new DeferredSourceDefinitionsMutable(
                static fn () => [],
                static fn () => [Foo::class => true]
            ),
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: true,
                useAttribute: false,
            ),
        );

        self::assertSame(
            [Foo::class => true],
            [...$container->getRemovedDefinitionIds()]
        );
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
