<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByAutowireAttribute;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Tests\DiContainer\ResolveByAutowireAttribute\Fixtures\One;
use Tests\DiContainer\ResolveByAutowireAttribute\Fixtures\Two;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\Autowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 *
 * @internal
 */
class ResolveByAutowireTest extends TestCase
{
    public function testAutowireAttributeWithSingletonTrueButContainerSetDefaultSingletonFalse(): void
    {
        $container = new DiContainer(config: new DiContainerConfig(
            isSingletonServiceDefault: false,
        ));

        $one = $container->get(One::class);

        $this->assertSame($one, $container->get(One::class));
    }

    public function testAutowireAttributeWithSingletonFalseButContainerSetDefaultSingletonTrue(): void
    {
        $container = new DiContainer(config: new DiContainerConfig(
            isSingletonServiceDefault: true,
        ));

        $two = $container->get(Two::class);

        $this->assertNotSame($two, $container->get(Two::class));
    }
}
