<?php

declare(strict_types=1);

namespace Tests\Integration\DiDefinitionRuntime;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\DiContainerBuilderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
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
    private DiContainerBuilderInterface $builder;
    private vfsStreamDirectory $rootDir;

    protected function setUp(): void
    {
        $this->builder = new DiContainerBuilder(
            containerConfig: new DiContainerConfig(
                useAttribute: true,
            )
        );
        $this->rootDir = vfsStream::setup('root');

        $containerClass = __NAMESPACE__.'\Container_'.bin2hex(random_bytes(16));

        $this->builder->import('Tests\\', __DIR__.'/Fixtures')
            ->load(__DIR__.'/Fixtures/services.php')
            ->compileToFile(vfsStream::url('root/'), $containerClass, isExclusiveLockFile: false)
        ;
    }

    protected function tearDown(): void
    {
        unset($this->builder, $this->rootDir);
    }

    public function testReplaceRuntimeDefinitionSuccess(): void
    {
        $container = $this->builder->build();

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

        $container = $this->builder->build();

        self::assertTrue($container->has('service.foo'));

        $container->get(FooAttr::class);
    }

    public function testDiDefinitionRuntimeOnParameter(): void
    {
        $this->expectException(ContainerBuilderExceptionInterface::class);
        $this->expectExceptionMessage('Cannot compile arguments');

        vfsStream::newFile('redefine_service.php')
            ->setContent('<?php
use Tests\Integration\DiDefinitionRuntime\Fixtures\Foo;

return static function (\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface $configurator) {
    $configurator->setDefinition(
        Foo::class,
        \Kaspi\DiContainer\diAutowire(Foo::class)
            ->bindArguments(
                service: \Kaspi\DiContainer\diRuntime("service.foo")
            )
    );
};
')
            ->at($this->rootDir)
        ;

        $this->builder->load(vfsStream::url('root/redefine_service.php'));

        $this->builder->build();
    }
}
