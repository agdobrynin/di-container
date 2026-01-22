<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionProxyClosure;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\ProxyClosure;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionProxyClosure\Fixtures\AnyClass;
use Tests\DiDefinition\DiDefinitionProxyClosure\Fixtures\One;
use Tests\DiDefinition\DiDefinitionProxyClosure\Fixtures\Tow;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diProxyClosure;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diProxyClosure')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(ProxyClosure::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionProxyClosure::class)]
#[CoversClass(Helper::class)]
#[CoversClass(BindArgumentsTrait::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
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
