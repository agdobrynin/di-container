<?php

declare(strict_types=1);

namespace Tests\Integration\FactoryCompile;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\Integration\FactoryCompile\Fixtures\Bar;
use Tests\Integration\FactoryCompile\Fixtures\BarFactoryStatic;
use Tests\Integration\FactoryCompile\Fixtures\Baz;
use Tests\Integration\FactoryCompile\Fixtures\Foo;

use function bin2hex;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diFactory;
use function random_bytes;

/**
 * @internal
 */
#[CoversNothing]
class FactoryTest extends TestCase
{
    public function testFactoryCompile(): void
    {
        vfsStream::setup();
        $containerClass = 'Container'.bin2hex(random_bytes(16));

        $config = new DiContainerConfig(
            // 🚩 Запрет автопоиска, использовать только то что добавлено
            useZeroConfigurationDefinition: false,
        );
        $builder = (new DiContainerBuilder($config))
            ->import(
                'Tests\Integration\FactoryCompile\Fixtures\\',
                __DIR__.'/Fixtures'
            )
            ->addDefinitions([
                Bar::class => diFactory([BarFactoryStatic::class, 'create'])
                    ->bindArguments(
                        diAutowire(Baz::class)
                            ->setupImmutable('withStr', 'Ho-ho-ho'),
                    ),
            ])
            ->compileToFile(vfsStream::url('root/'), '\App\\'.$containerClass, isExclusiveLockFile: false)
        ;

        $container = $builder->build();

        self::assertEquals('Ho-ho-ho', $container->get(Bar::class)->baz->str);
        self::assertEquals('Lorem ipsum', $container->get(Foo::class)->strFoo);
    }
}
