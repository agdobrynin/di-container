<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\Exceptions\DiContainerConfigExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\DiContainerConfig
 *
 * @internal
 */
class DiContainerConfigTest extends TestCase
{
    public function testContainerConfigException(): void
    {
        $this->expectException(DiContainerConfigExceptionInterface::class);
        $this->expectExceptionMessage('cannot be empty');

        new DiContainerConfig(referenceContainerSymbol: '');
    }

    public function dataProviderGetReferenceToContainer(): \Generator
    {
        yield 'success' => [
            'referenceContainerSymbol' => '=>',
            'key' => '=>container-id',
            'expect' => 'container-id',
        ];

        yield 'link not detected' => [
            'referenceContainerSymbol' => '@',
            'key' => '=>container-id',
            'expect' => null,
        ];
    }

    /**
     * @dataProvider dataProviderGetReferenceToContainer
     */
    public function testGetReferenceToContainer(string $referenceContainerSymbol, string $key, ?string $expect): void
    {
        $config = new DiContainerConfig(referenceContainerSymbol: $referenceContainerSymbol);

        $this->assertEquals($expect, $config->getReferenceToContainer($key));
    }

    public function dataProviderUseZeroConfigurationDefinition(): \Generator
    {
        yield 'default zero config available' => [null, true];

        yield 'set TRUE' => [true, true];

        yield 'set FALSE' => [false, false];
    }

    /**
     * @dataProvider dataProviderUseZeroConfigurationDefinition
     */
    public function testIsUseZeroConfigurationDefinition(null|bool $value, bool $expect): void
    {
        $conf = null === $value
            ? new DiContainerConfig()
            : new DiContainerConfig(useZeroConfigurationDefinition: $value);

        $this->assertEquals($expect, $conf->isUseZeroConfigurationDefinition());
    }

    public function testUsePhpAttributeWithoutAutowire(): void
    {
        $this->expectException(DiContainerConfigExceptionInterface::class);

        new DiContainerConfig(useAutowire: false, useAttribute: true);
    }
}
