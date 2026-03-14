<?php

declare(strict_types=1);

namespace Tests\ContainerBuilder;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerNullConfig;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\ContainerBuilder\Fixtures2\Baz;

use function array_diff;
use function array_keys;
use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversNothing]
class ConfiguratorTest extends TestCase
{
    public function testUseConfigurator(): void
    {
        $container = (new DiContainerBuilder(
            new DiContainerNullConfig()
        ))
            ->import('Tests\ContainerBuilder\Fixtures\\', __DIR__.'/Fixtures')
            ->addDefinitions([
                diAutowire(Baz::class),
            ])
            ->load(__DIR__.'/Fixtures/config2_services.php')
            // configure definition via `DefinitionsConfiguratorInterface`
            ->load(__DIR__.'/Fixtures/tagging_services.php')
            ->build()
        ;

        $tagged_keys = array_keys([...$container->findTaggedDefinitions('tags.qux')]);
        $all_keys = array_keys([...$container->getDefinitions()]);

        // configured definitions in file __DIR__.'/Fixtures/tagging_services.php'
        self::assertEmpty(
            array_diff(
                [
                    'Tests\ContainerBuilder\Fixtures2\Bat',
                    'Tests\ContainerBuilder\Fixtures\Foo',
                    'Tests\ContainerBuilder\Fixtures2\Baz',
                ],
                $tagged_keys
            )
        );

        // all loaded definitions
        self::assertEmpty(
            array_diff(
                [
                    'Tests\ContainerBuilder\Fixtures\Qux',
                    'Tests\ContainerBuilder\Fixtures2\Bat',
                    'Tests\ContainerBuilder\Fixtures\Foo',
                    'Tests\ContainerBuilder\Fixtures2\Baz',
                ],
                $all_keys
            )
        );
    }
}
