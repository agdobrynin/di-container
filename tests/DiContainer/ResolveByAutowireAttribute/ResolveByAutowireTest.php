<?php

declare(strict_types=1);

namespace Tests\DiContainer\ResolveByAutowireAttribute;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\DiContainer\ResolveByAutowireAttribute\Fixtures\One;
use Tests\DiContainer\ResolveByAutowireAttribute\Fixtures\Two;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(Autowire::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(SourceDefinitionsMutable::class)]
#[CoversClass(Helper::class)]
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
