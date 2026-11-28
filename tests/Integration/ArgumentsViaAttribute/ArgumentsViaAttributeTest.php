<?php

declare(strict_types=1);

namespace Tests\Integration\ArgumentsViaAttribute;

use Kaspi\DiContainer\DiContainerBuilder;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\Integration\ArgumentsViaAttribute\Fixtures\Bar;
use Tests\Integration\ArgumentsViaAttribute\Fixtures\Bat;
use Tests\Integration\ArgumentsViaAttribute\Fixtures\Bom;
use Tests\Integration\ArgumentsViaAttribute\Fixtures\Foo;
use Tests\Integration\ArgumentsViaAttribute\Fixtures\FooService;

use function bin2hex;
use function random_bytes;

/**
 * @internal
 */
#[CoversNothing]
class ArgumentsViaAttributeTest extends TestCase
{
    public function testRuntimeContainer(): void
    {
        $container = (new DiContainerBuilder())
            ->import('Tests\\', __DIR__.'/Fixtures')
            ->addParameters([
                'params.foo' => 'foo',
                'adminEmail' => 'admin@localhost',
            ])
            ->build()
        ;

        $fooService = $container->get(FooService::class);

        self::assertInstanceOf(Foo::class, $fooService->qux);
        self::assertInstanceOf(Bat::class, $fooService->qux->quuux);

        self::assertEquals('', $fooService->qux->quuux->param);
        self::assertEquals('admin@localhost', $fooService->adminEmail);

        $fooServiceBar = $container->get('services.foo_bar');

        self::assertInstanceOf(Bar::class, $fooServiceBar->qux);
        self::assertInstanceOf(Bom::class, $fooServiceBar->qux->quuux);

        self::assertEquals('foo', $fooServiceBar->qux->quuux->param);
        self::assertEquals('admin@localhost', $fooServiceBar->adminEmail);
    }

    public function testCompiledContainer(): void
    {
        $containerClass = 'Container'.bin2hex(random_bytes(5));
        vfsStream::setup('app');

        $container = (new DiContainerBuilder())
            ->import('Tests\\', __DIR__.'/Fixtures')
            ->addParameters([
                'params.foo' => 'foo',
                'adminEmail' => 'admin@localhost',
            ])
            ->compileToFile(
                vfsStream::url('app'),
                'App\\'.$containerClass,
                isExclusiveLockFile: false,
            )
            ->build()
        ;

        $fooService = $container->get(FooService::class);

        self::assertInstanceOf(Foo::class, $fooService->qux);
        self::assertInstanceOf(Bat::class, $fooService->qux->quuux);

        self::assertEquals('', $fooService->qux->quuux->param);
        self::assertEquals('admin@localhost', $fooService->adminEmail);

        $fooServiceBar = $container->get('services.foo_bar');

        self::assertInstanceOf(Bar::class, $fooServiceBar->qux);
        self::assertInstanceOf(Bom::class, $fooServiceBar->qux->quuux);

        self::assertEquals('foo', $fooServiceBar->qux->quuux->param);
        self::assertEquals('admin@localhost', $fooServiceBar->adminEmail);
    }
}
