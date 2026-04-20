<?php

declare(strict_types=1);

namespace Tests\Integration\ContainerParameterRuntime;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\DiContainerBuilderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface;
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
    protected DiContainerBuilderInterface $containerBuilder;

    protected function setUp(): void
    {
        $containerClass = 'Container_'.bin2hex(random_bytes(16));

        vfsStream::setup('root');

        $this->containerBuilder = (new DiContainerBuilder(
            new DiContainerConfig(
                useZeroConfigurationDefinition: false
            )
        ))
            ->addDefinitions([
                // Use php attribute for configure parameters
                diAutowire(Bar::class),
                diAutowire(Foo::class)
                    ->bindArguments(
                        diParameterRuntime(),
                        baz: diParameterRuntime('qux.value')
                    ),
            ])
            ->compileToFile(vfsStream::url('root/'), $containerClass, isExclusiveLockFile: false)
        ;
    }

    protected function tearDown(): void
    {
        unset($this->containerBuilder);
    }

    public function testCompileDiDefinitionParameterRuntime(): void
    {
        $container = $this->containerBuilder->build();
        $container->parameters()->add(['bar' => 'ola', 'qux.value' => 1_000]);

        self::assertEquals(['bar' => 'ola', 'baz' => 1_000], (array) $container->get(Foo::class));

        $container->parameters()->set('bat', 'aloha');

        self::assertEquals(['bat' => 'aloha', 'qux' => 1_000], (array) $container->get(Bar::class));
    }

    public function testParameterRuntimeNotDefined(): void
    {
        $this->expectException(ParameterNotFoundExceptionInterface::class);
        $this->expectExceptionMessage('The container parameter "qux.value" must be set in the container at runtime');

        $container = $this->containerBuilder->build();
        $container->parameters()->set('bat', 'aloha');
        $container->get(Bar::class);
    }

    public function testCannotCompileParameterRuntimeWhenParameterAlreadyRegistered(): void
    {
        $this->expectException(ContainerBuilderExceptionInterface::class);
        $this->expectExceptionMessage('Tests\Integration\ContainerParameterRuntime\Fixtures\Bar::__construct()');

        /*
         * The parameter 'qux.value' defined before compile,
         * this definition fire exception because parameter Bar::$qux
         * has php attribute #[ParameterRuntime('qux.value')].
         * The parameter runtime must be defined in runtime container.
        */
        $this->containerBuilder->addParameters(['qux.value' => 1_000]);
        $this->containerBuilder->build();
    }
}
