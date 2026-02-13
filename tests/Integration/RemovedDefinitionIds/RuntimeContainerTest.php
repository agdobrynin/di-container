<?php

declare(strict_types=1);

namespace Tests\Integration\RemovedDefinitionIds;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\Integration\RemovedDefinitionIds\Fixtures\Foo;
use Tests\Integration\RemovedDefinitionIds\Fixtures\SubDir\Bar;

/**
 * @internal
 */
#[CoversNothing]
class RuntimeContainerTest extends TestCase
{
    public function testRemovedDefinitionIds(): void
    {
        $container = (new DiContainerBuilder(
            new DiContainerConfig(
                useZeroConfigurationDefinition: true,
            )
        ))
            ->import(
                'Tests\Integration\RemovedDefinitionIds\\',
                __DIR__.'/Fixtures',
                excludeFiles: [__DIR__.'/Fixtures/SubDir/*'],
            )
            ->build()
        ;

        self::assertFalse($container->has(Bar::class));
        self::assertTrue($container->has(Foo::class));

        $container->set(Bar::class, new Bar());

        self::assertTrue($container->has(Bar::class));
    }
}
