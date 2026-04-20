<?php

declare(strict_types=1);

namespace Tests\Integration\ContainerParameterRuntime;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterNotFoundExceptionInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\Integration\ContainerParameterRuntime\Fixtures\Bar;
use Tests\Integration\ContainerParameterRuntime\Fixtures\Foo;

use function bin2hex;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diParameterRuntime;
use function random_bytes;

/**
 * @internal
 */
#[CoversNothing]
class ContainerParameterRuntimeOnCompiledContainerTest extends TestCase
{
    protected DiContainerInterface $container;

    protected function setUp(): void
    {
        $containerClass = 'Container_'.bin2hex(random_bytes(16));

        vfsStream::setup('root');

        $this->container = (new DiContainerBuilder(
            new DiContainerConfig(
                useZeroConfigurationDefinition: false
            )
        ))
            ->addDefinitions([
                diAutowire(Foo::class)
                    ->bindArguments(
                        diParameterRuntime(),
                        baz: diParameterRuntime('qux.value')
                    ),
                // Use php attribute for configure parameters
                diAutowire(Bar::class),
            ])
            ->compileToFile(vfsStream::url('root/'), $containerClass, isExclusiveLockFile: false)
            ->build()
        ;
    }

    protected function tearDown(): void
    {
        unset($this->container);
    }

    public function testCompileDiDefinitionParameterRuntime(): void
    {
        $this->container->parameters()->add(['bar' => 'ola', 'qux.value' => 1_000]);

        self::assertEquals(['bar' => 'ola', 'baz' => 1_000], (array) $this->container->get(Foo::class));

        $this->container->parameters()->set('bat', 'aloha');

        self::assertEquals(['bat' => 'aloha', 'qux' => 1_000], (array) $this->container->get(Bar::class));
    }

    public function testParameterRuntimeNotDefined(): void
    {
        $this->expectException(ParameterNotFoundExceptionInterface::class);
        $this->expectExceptionMessage('The container parameter "qux.value" must be set in the container at runtime');

        $this->container->parameters()->set('bat', 'aloha');
        $this->container->get(Bar::class);
    }
}
