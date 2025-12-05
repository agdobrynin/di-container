<?php

declare(strict_types=1);

namespace Tests\DiContainerConfig;

use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DiContainerConfig::class)]
class MainTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $config = new DiContainerConfig();

        $this->assertTrue($config->isUseZeroConfigurationDefinition());
        $this->assertTrue($config->isUseAttribute());
        $this->assertFalse($config->isSingletonServiceDefault());
    }

    public function testUserValues(): void
    {
        $config = new DiContainerConfig(
            useZeroConfigurationDefinition: false,
            useAttribute: false,
            isSingletonServiceDefault: true,
        );

        $this->assertFalse($config->isUseZeroConfigurationDefinition());
        $this->assertFalse($config->isUseAttribute());
        $this->assertTrue($config->isSingletonServiceDefault());
    }
}
