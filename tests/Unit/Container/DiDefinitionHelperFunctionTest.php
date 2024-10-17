<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

use function Kaspi\DiContainer\diDefinition;

/**
 * @covers \Kaspi\DiContainer\diDefinition
 *
 * @internal
 */
class DiDefinitionHelperFunctionTest extends TestCase
{
    public function dataProviderForException(): \Generator
    {
        yield 'all default' => [
            null, null, null,
        ];

        yield 'args empty array' => [
            null, [], null,
        ];

        yield 'definition empty array' => [
            [], null, null,
        ];

        yield 'definition empty string' => [
            '', null, null,
        ];

        yield 'definition boolean false' => [
            false, [], null,
        ];
    }

    /**
     * @dataProvider dataProviderForException
     */
    public function testException(mixed $definition, ?array $arguments, ?bool $isSingleton): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        diDefinition($definition, $arguments, $isSingleton);
    }

    public function dataProvideSuccess(): \Generator
    {
        yield 'has definition without args and singleton' => [
            self::class, null, null, [self::class],
        ];

        yield 'has only args' => [
            null, ['name' => 'Ivan', 'service' => '@app.serviceOne'], null, ['arguments' => ['name' => 'Ivan', 'service' => '@app.serviceOne']],
        ];

        yield 'has only singleton true' => [
            null, [], true, ['singleton' => true],
        ];

        yield 'has only singleton false' => [
            null, [], false, ['singleton' => false],
        ];
    }

    /**
     * @dataProvider dataProvideSuccess
     */
    public function testSuccess(mixed $definition, ?array $arguments, ?bool $isSingleton, array $expect): void
    {
        $this->assertEquals($expect, diDefinition($definition, $arguments, $isSingleton));
    }
}
