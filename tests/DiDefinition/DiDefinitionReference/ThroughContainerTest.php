<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionReference;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitionsMutable;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionReference\Fixtures\AnyClass;
use Tests\DiDefinition\DiDefinitionReference\Fixtures\AnyOneService;
use Tests\DiDefinition\DiDefinitionReference\Fixtures\AnyTwoService;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(\Kaspi\DiContainer\Attributes\Inject::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(Helper::class)]
#[CoversClass(BindArgumentsTrait::class)]
#[CoversClass(SourceDefinitionsMutable::class)]
class ThroughContainerTest extends TestCase
{
    public function testOverrideDiGetAsPhpAttributeInject(): void
    {
        $config = new DiContainerConfig(useAttribute: true); // 🚩
        $container = new DiContainer([
            diAutowire(AnyClass::class) // <-- define by php-attribute #[Inject(AnyTwoService::class)]
                ->bindArguments(any: diGet(AnyOneService::class)),
        ], $config);

        $this->assertInstanceOf(AnyTwoService::class, $container->get(AnyClass::class)->any);
    }

    public function testNotOverrideDiGetAsPhpAttributeInject(): void
    {
        $config = new DiContainerConfig(useAttribute: false); // 🚩
        $container = new DiContainer([
            diAutowire(AnyClass::class) // <-- define by php-attribute #[Inject(AnyTwoService::class)]
                ->bindArguments(any: diGet(AnyOneService::class)),
        ], $config);

        $this->assertInstanceOf(AnyOneService::class, $container->get(AnyClass::class)->any);
    }
}
