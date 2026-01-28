<?php

declare(strict_types=1);

namespace Tests\Integration;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function random_bytes;

/**
 * @internal
 */
#[CoversNothing]
class ResolveExcludedIdsTest extends TestCase
{
    public function testResolveExcludedIdsOnCompiledContainer(): void
    {
        vfsStream::setup();
        $containerClass = '\App\Container'.bin2hex(random_bytes(16));

        $config = new DiContainerConfig(
            // 🚩 Запрет автопоиска, использовать только то что добавлено
            useZeroConfigurationDefinition: false,
        );
        $builder = (new DiContainerBuilder($config))
            ->import(
                'Tests\Integration\Fixtures\ResolveExcludedIds\\',
                __DIR__.'/Fixtures/ResolveExcludedIds'
            )
            ->compileToFile(vfsStream::url('root/'), $containerClass, isExclusiveLockFile: false)
        ;

        $container = $builder->build();

        self::assertEquals(
            ['firstName' => 'Ivan', 'lastName' => 'Petrov', 'age' => 22],
            (array) $container->get(Fixtures\ResolveExcludedIds\Other::class)->person
        );
    }
}
