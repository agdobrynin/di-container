<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\DiContainerConfig
 *
 * @internal
 */
class DiContainerConfigTest extends TestCase
{
    public function dataProviderUseZeroConfigurationDefinition(): \Generator
    {
        yield 'default zero config available' => [null, true];

        yield 'set TRUE' => [true, true];

        yield 'set FALSE' => [false, false];
    }

    /**
     * @dataProvider dataProviderUseZeroConfigurationDefinition
     */
    public function testIsUseZeroConfigurationDefinition(?bool $value, bool $expect): void
    {
        $conf = null === $value
            ? new DiContainerConfig()
            : new DiContainerConfig(useZeroConfigurationDefinition: $value);

        $this->assertEquals($expect, $conf->isUseZeroConfigurationDefinition());
    }
}
