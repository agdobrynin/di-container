<?php

declare(strict_types=1);

namespace Tests\Integration\DiDefinitionRuntime;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\Integration\DiDefinitionRuntime\Fixtures\Foo;
use Tests\Integration\DiDefinitionRuntime\Fixtures\FooAttr;

use function bin2hex;
use function random_bytes;

/**
 * @internal
 */
#[CoversNothing]
class DiDefinitionRuntimeInCompiledContainerTest extends TestCase
{
    public function testReplaceRuntimeDefinitionSuccess(): void
    {
        vfsStream::setup('root');
        $containerClass = __NAMESPACE__.'\Container_'.bin2hex(random_bytes(16));

        $container = (new DiContainerBuilder(
            containerConfig: new DiContainerConfig(
                useAttribute: true,
            )
        ))
            ->import('Tests\\', __DIR__.'/Fixtures')
            ->load(__DIR__.'/Fixtures/services.php')
            ->compileToFile(vfsStream::url('root/'), $containerClass, isExclusiveLockFile: false)
            ->build()
        ;

        self::assertTrue($container->has('service.foo'));

        $instance = (object) ['foo' => 'Service bar'];

        $container->set('service.foo', $instance);

        // service configured via php attribute `Inject`
        self::assertSame($instance, $container->get(FooAttr::class)->service);
        // service configured via `bindArguments()`
        self::assertSame($instance, $container->get(Foo::class)->service);
    }

    public function testReplaceRuntimeDefinitionFail(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('The runtime definition with container identifier \'service.foo\' cannot be resolved');

        vfsStream::setup('root');
        $containerClass = __NAMESPACE__.'\Container_'.bin2hex(random_bytes(16));

        // The definition at container identifier `'service.foo'` not replaced via `$container->set('service.foo', $instance)`
        $container = (new DiContainerBuilder(
            containerConfig: new DiContainerConfig(
                useAttribute: true,
            )
        ))
            ->import('Tests\\', __DIR__.'/Fixtures')
            ->load(__DIR__.'/Fixtures/services.php')
            ->compileToFile(vfsStream::url('root/'), $containerClass, isExclusiveLockFile: false)
            ->build()
        ;

        self::assertTrue($container->has('service.foo'));

        $container->get(FooAttr::class);
    }
}
