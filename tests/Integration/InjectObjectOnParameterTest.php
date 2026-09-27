<?php

declare(strict_types=1);

namespace Tests\Integration;

use Generator;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerBuilder;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Tests\Integration\Fixtures\InjectLazyObjectOnParameter\Bar;
use Tests\Integration\Fixtures\InjectLazyObjectOnParameter\Baz;
use Tests\Integration\Fixtures\InjectLazyObjectOnParameter\BazArgOnly;
use Tests\Integration\Fixtures\InjectLazyObjectOnParameter\Foo;
use Tests\Integration\Fixtures\InjectLazyObjectOnParameter\FooArgOnly;

/**
 * @internal
 */
#[CoversNothing]
class InjectObjectOnParameterTest extends TestCase
{
    #[RequiresPhp('>= 8.4')]
    #[DataProvider('dataProviderForPhp84')]
    public function testLazyAutowireOnParameter(DiContainer $container): void
    {
        $foo = $container->get(Foo::class);

        $reflectionFooBar = new ReflectionObject($foo->bar);

        self::assertTrue($reflectionFooBar->isUninitializedLazyObject($foo->bar));
        self::assertEquals('bar value', $foo->bar->val);
        self::assertFalse($reflectionFooBar->isUninitializedLazyObject($foo->bar));

        $reflectionFooBaz = new ReflectionObject($foo->baz);

        self::assertTrue($reflectionFooBaz->isUninitializedLazyObject($foo->baz));
        self::assertEquals('baz value', $foo->baz->val);
        self::assertFalse($reflectionFooBaz->isUninitializedLazyObject($foo->baz));

        $bar = $container->get(Bar::class);
        $reflectionBar = new ReflectionObject($bar);

        self::assertFalse($reflectionBar->isUninitializedLazyObject($bar));
        self::assertEquals('Lorem ipsum', $bar->val);

        $baz = $container->get(Baz::class);
        $reflectionBaz = new ReflectionObject($baz);

        self::assertTrue($reflectionBaz->isUninitializedLazyObject($baz));
        self::assertEquals('Dol uni', $baz->val);
        self::assertFalse($reflectionBaz->isUninitializedLazyObject($baz));
    }

    public static function dataProviderForPhp84(): Generator
    {
        yield 'runtime container' => [
            (new DiContainerBuilder())
                ->import('Tests\\', __DIR__.'/Fixtures/InjectLazyObjectOnParameter')
                ->build(),
        ];

        yield 'compiled container' => [
            (new DiContainerBuilder())
                ->import('Tests\\', __DIR__.'/Fixtures/InjectLazyObjectOnParameter')
                ->compileToFile(vfsStream::setup()->url(), 'Container', isExclusiveLockFile: false)
                ->build(),
        ];
    }

    #[DataProvider('dataProviderForPhp81')]
    public function testAutowireOnParameter(DiContainer $container): void
    {
        $foo = $container->get(FooArgOnly::class);

        self::assertEquals('bar value', $foo->bar->val);
        self::assertEquals('baz value', $foo->baz->val);

        $bar = $container->get(Bar::class);
        self::assertEquals('Lorem ipsum', $bar->val);

        $baz = $container->get(BazArgOnly::class);
        self::assertEquals('Dol uni', $baz->val);
    }

    public static function dataProviderForPhp81(): Generator
    {
        $excludeFiles = ['*/Foo.php', '*/Baz.php'];

        yield 'runtime container' => [
            (new DiContainerBuilder())
                ->import('Tests\\', __DIR__.'/Fixtures/InjectLazyObjectOnParameter', excludeFiles: $excludeFiles)
                ->build(),
        ];

        yield 'compiled container' => [
            (new DiContainerBuilder())
                ->import('Tests\\', __DIR__.'/Fixtures/InjectLazyObjectOnParameter', excludeFiles: $excludeFiles)
                ->compileToFile(vfsStream::setup()->url(), 'Container', isExclusiveLockFile: false)
                ->build(),
        ];
    }
}
