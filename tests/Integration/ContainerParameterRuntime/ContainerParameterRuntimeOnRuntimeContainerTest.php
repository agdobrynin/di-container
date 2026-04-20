<?php

declare(strict_types=1);

namespace Tests\Integration\ContainerParameterRuntime;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\Integration\ContainerParameterRuntime\Fixtures\Bar;
use Tests\Integration\ContainerParameterRuntime\Fixtures\Foo;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diParameterRuntime;

/**
 * @internal
 */
#[CoversNothing]
class ContainerParameterRuntimeOnRuntimeContainerTest extends TestCase
{
    protected DiContainerInterface $container;

    protected function setUp(): void
    {
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
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot resolve parameter at position #1');

        $this->container->parameters()->set('bat', 'aloha');
        $this->container->get(Bar::class);
    }
}
