<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\Autowired;
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
    public function dataProviderConstructorException(): \Generator
    {
        yield 'all symbols empty' => [
            'linkContainerSymbol' => '', 'delimiterAccessArrayNotationSymbol' => '', 'msg' => 'symbol cannot be empty',
        ];

        yield 'linkContainerSymbol empty' => [
            'linkContainerSymbol' => '', 'delimiterAccessArrayNotationSymbol' => '.', 'msg' => 'symbol cannot be empty',
        ];

        yield 'delimiter access array notation symbol empty' => [
            'linkContainerSymbol' => '@', 'delimiterAccessArrayNotationSymbol' => '', 'msg' => 'symbol cannot be empty',
        ];

        yield 'symbols must be diff' => [
            'linkContainerSymbol' => '.', 'delimiterAccessArrayNotationSymbol' => '.', 'msg' => 'Delimiters symbols must be different',
        ];
    }

    /**
     * @dataProvider dataProviderConstructorException
     */
    public function testContainerConfigException(?string $linkContainerSymbol, ?string $delimiterAccessArrayNotationSymbol, string $msg): void
    {
        $this->expectException(DiContainerConfigExceptionInterface::class);
        $this->expectExceptionMessage($msg);

        new DiContainerConfig(linkContainerSymbol: $linkContainerSymbol, delimiterAccessArrayNotationSymbol: $delimiterAccessArrayNotationSymbol);
    }

    public function dataProviderContainerConfigSymbols(): \Generator
    {
        yield 'default values' => [
            'args' => [],
            'linkContainerSymbol' => '@',
            'isUseLinkContainerDefinition' => true,
            'delimiterAccessArrayNotationSymbol' => '.',
            'isUseArrayNotationDefinition' => true,
        ];

        yield 'null values' => [
            'args' => ['linkContainerSymbol' => null, 'delimiterAccessArrayNotationSymbol' => null],
            'linkContainerSymbol' => null,
            'isUseLinkContainerDefinition' => false,
            'delimiterAccessArrayNotationSymbol' => null,
            'isUseArrayNotationDefinition' => false,
        ];
    }

    /**
     * @dataProvider dataProviderContainerConfigSymbols
     */
    public function testContainerConfigSymbols(array $args, ?string $linkContainerSymbol, bool $isUseLinkContainerDefinition, ?string $delimiterAccessArrayNotationSymbol, bool $isUseArrayNotationDefinition): void
    {
        $newArgs = \array_merge($args, ['useAttribute' => false]);
        $conf = new DiContainerConfig(...$newArgs);

        $this->assertEquals($linkContainerSymbol, $conf->getLinkContainerSymbol());
        $this->assertEquals($isUseLinkContainerDefinition, $conf->isUseLinkContainerDefinition());

        $this->assertEquals($delimiterAccessArrayNotationSymbol, $conf->getDelimiterAccessArrayNotationSymbol());
        $this->assertEquals($isUseArrayNotationDefinition, $conf->isUseArrayNotationDefinition());
    }

    public function dataProviderGetLinkContainerSymbol(): \Generator
    {
        yield 'success' => [
            'linkContainerSymbol' => '=>',
            'key' => '=>container-id',
            'expect' => 'container-id',
        ];

        yield 'link not detected' => [
            'linkContainerSymbol' => '@',
            'key' => '=>container-id',
            'expect' => null,
        ];

        yield 'linkContainerSymbol is null' => [
            'linkContainerSymbol' => null,
            'key' => '@container-id',
            'expect' => null,
        ];
    }

    /**
     * @dataProvider dataProviderGetLinkContainerSymbol
     */
    public function testGetKeyFromLinkContainerSymbol(?string $linkContainerSymbol, string $key, ?string $expect): void
    {
        $config = new DiContainerConfig(linkContainerSymbol: $linkContainerSymbol, useAttribute: false);

        $this->assertEquals($expect, $config->getKeyFromLinkContainerSymbol($key));
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
            ? new DiContainerConfig(useAttribute: false)
            : new DiContainerConfig(useZeroConfigurationDefinition: $value, useAttribute: false);

        $this->assertEquals($expect, $conf->isUseZeroConfigurationDefinition());
    }

    public function dataProviderAutowire(): \Generator
    {
        yield 'default value' => [null, ['useAttribute' => false]];

        yield 'set available' => [new Autowired(), ['autowire' => new Autowired()]];

        yield 'set force null' => [null, ['autowire' => null, 'useAttribute' => false]];
    }

    /**
     * @dataProvider dataProviderAutowire
     *
     * @param mixed $expect
     */
    public function testAutowire($expect, array $autowired): void
    {
        $conf = new DiContainerConfig(...$autowired);
        $this->assertEquals($expect, $conf->getAutowire());
    }

    public function dataProviderIsArrayNotationSyntaxSyntax(): \Generator
    {
        yield 'default values' => [
            'args' => [],
            'key' => '@aaa.ddd',
            'expect' => true,
        ];

        yield 'key not array notation with default values' => [
            'args' => [],
            'key' => 'aaa.ddd',
            'expect' => false,
        ];

        yield 'custom values and success' => [
            'args' => ['linkContainerSymbol' => '-->', 'delimiterAccessArrayNotationSymbol' => '~'],
            'key' => '-->aaa~ddd',
            'expect' => true,
        ];

        yield 'custom values and fail' => [
            'args' => ['linkContainerSymbol' => '-->', 'delimiterAccessArrayNotationSymbol' => '~'],
            'key' => '-->aaa-->ddd',
            'expect' => false,
        ];

        yield 'null value for link symbol' => [
            'args' => ['linkContainerSymbol' => null, 'delimiterAccessArrayNotationSymbol' => '~'],
            'key' => 'aaa~ddd',
            'expect' => false,
        ];

        yield 'null values' => [
            'args' => ['linkContainerSymbol' => null, 'delimiterAccessArrayNotationSymbol' => null],
            'key' => '@aaa.ddd',
            'expect' => false,
        ];
    }

    /**
     * @dataProvider dataProviderIsArrayNotationSyntaxSyntax
     */
    public function testIsArrayNotationSyntaxSyntax(array $args, string $key, bool $expect): void
    {
        $newArgs = \array_merge($args, ['autowire' => new Autowired()]);
        $this->assertEquals(
            $expect,
            (new DiContainerConfig(...$newArgs))->isArrayNotationSyntaxSyntax($key)
        );
    }
}
