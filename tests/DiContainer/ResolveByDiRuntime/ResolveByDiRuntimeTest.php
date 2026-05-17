<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByDiRuntime;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionRuntime;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionRuntimeInterface;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\DiContainer\ResolveByDiRuntime\Fixtures\Bar;
use Tests\DiContainer\ResolveByDiRuntime\Fixtures\Foo;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiRuntime::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionRuntime::class)]
#[CoversClass(ImmediateSourceParameters::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainer::class)]
class ResolveByDiRuntimeTest extends TestCase
{
    private DiContainer $diContainer;

    protected function setUp(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: true,
            useAttribute: true
        );

        $this->diContainer = new DiContainer(config: $config);
    }

    protected function tearDown(): void
    {
        unset($this->diContainer);
    }

    public function testResolveByDiRuntime(): void
    {
        self::assertInstanceOf(DiDefinitionAutowireInterface::class, $this->diContainer->getDefinition(Bar::class));
        self::assertInstanceOf(DiDefinitionRuntimeInterface::class, $this->diContainer->getDefinition(Foo::class));
    }

    public function testGetRuntimeDefinitionFail(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('You should replace the value of definition in the runtime container');

        self::assertTrue($this->diContainer->has(Foo::class));

        $this->diContainer->get(Foo::class);
    }

    public function testGetRuntimeDefinitionSuccess(): void
    {
        $this->diContainer->getDefinition(Foo::class)
            ->setDefinition(new Foo())
        ;

        self::assertInstanceOf(Foo::class, $this->diContainer->get(Foo::class));
    }
}
