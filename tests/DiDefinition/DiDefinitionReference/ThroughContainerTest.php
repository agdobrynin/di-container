<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionReference;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionReference\Fixtures\AnyClass;
use Tests\DiDefinition\DiDefinitionReference\Fixtures\AnyOneService;
use Tests\DiDefinition\DiDefinitionReference\Fixtures\AnyTwoService;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Helper
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 */
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
