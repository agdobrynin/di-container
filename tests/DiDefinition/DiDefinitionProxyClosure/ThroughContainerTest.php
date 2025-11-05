<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionProxyClosure;

use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionProxyClosure\Fixtures\AnyClass;
use Tests\DiDefinition\DiDefinitionProxyClosure\Fixtures\One;
use Tests\DiDefinition\DiDefinitionProxyClosure\Fixtures\Tow;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diProxyClosure;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Attributes\ProxyClosure
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\BuildArguments
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure
 * @covers \Kaspi\DiContainer\diProxyClosure
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 */
class ThroughContainerTest extends TestCase
{
    public function testOverrideDiProxyClosureAsPhpAttributeProxyClosure(): void
    {
        $config = new DiContainerConfig(useAttribute: true); // 🚩
        $container = new DiContainer([
            diAutowire(AnyClass::class) // <-- define by php-attribute #[ProxyClosure(Tow::class)]
                ->bindArguments(service: diProxyClosure(One::class)),
        ], $config);

        $this->assertInstanceOf(Tow::class, ($container->get(AnyClass::class)->service)());
    }

    public function testNonOverrideDiProxyClosureAsPhpAttributeProxyClosure(): void
    {
        $config = new DiContainerConfig(useAttribute: false); // 🚩
        $container = new DiContainer([
            diAutowire(AnyClass::class) // <-- define by php-attribute #[ProxyClosure(Tow::class)]
                ->bindArguments(service: diProxyClosure(One::class)),
        ], $config);

        $this->assertInstanceOf(One::class, ($container->get(AnyClass::class)->service)());
    }
}
