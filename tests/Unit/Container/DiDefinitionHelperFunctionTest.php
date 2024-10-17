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
            null, null, null, null,
        ];

        yield 'args empty array' => [
            null, null, [], null,
        ];

        yield 'definition empty array' => [
            null, [], null, null,
        ];

        yield 'definition empty string' => [
            null, '', null, null,
        ];

        yield 'definition boolean false' => [
            null, false, [], null,
        ];

        yield 'has only id' => [
            self::class, null, null, null,
        ];

        yield 'has id and empty arguments' => [
            self::class, null, [], null,
        ];

        yield 'has id and empty definition' => [
            self::class, '', null, null,
        ];
    }

    /**
     * @dataProvider dataProviderForException
     */
    public function testException(?string $id, mixed $definition, ?array $arguments, ?bool $isSingleton): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        diDefinition(containerKey: $id, definition: $definition, arguments: $arguments, isSingleton: $isSingleton);
    }

    public function dataProvideSuccess(): \Generator
    {
        yield 'has definition without args and singleton' => [
            null, self::class, null, null, [self::class],
        ];

        yield 'has only args' => [
            null, null, ['name' => 'Ivan', 'service' => '@app.serviceOne'], null, ['arguments' => ['name' => 'Ivan', 'service' => '@app.serviceOne']],
        ];

        yield 'has only singleton true' => [
            null, null, [], true, ['singleton' => true],
        ];

        yield 'has only singleton false' => [
            null, null, [], false, ['singleton' => false],
        ];

        yield 'has id and singleton' => [
            self::class, null, null, false, [self::class => ['singleton' => false]],
        ];

        yield 'has id and arguments' => [
            self::class, null, ['name' => 'Ivan', 'service' => '@inject'], null, [self::class => ['arguments' => ['name' => 'Ivan', 'service' => '@inject']]],
        ];
    }

    /**
     * @dataProvider dataProvideSuccess
     */
    public function testSuccess(?string $containerKey, mixed $definition, ?array $arguments, ?bool $isSingleton, array $expect): void
    {
        $this->assertEquals($expect, diDefinition(containerKey: $containerKey, definition: $definition, arguments: $arguments, isSingleton: $isSingleton));
    }
}
