<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Fixtures\Classes\Interfaces\SumInterface;
use Tests\Fixtures\Classes\Sum;

use function Kaspi\DiContainer\diDefinition;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\diDefinition
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
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

        yield 'has definition and arguments' => [
            null, self::class, ['name' => 'Ivan', 'service' => '@inject'], null, [self::class, 'arguments' => ['name' => 'Ivan', 'service' => '@inject']],
        ];
    }

    /**
     * @dataProvider dataProvideSuccess
     */
    public function testSuccess(?string $containerKey, mixed $definition, ?array $arguments, ?bool $isSingleton, array $expect): void
    {
        $this->assertEquals($expect, diDefinition(containerKey: $containerKey, definition: $definition, arguments: $arguments, isSingleton: $isSingleton));
    }

    public function testFromReadme(): void
    {
        $d1 = diDefinition(
            containerKey: SumInterface::class,
            definition: Sum::class,
            arguments: ['init' => 50]
        );

        $d2 = diDefinition(
            containerKey: Sum::class,
            arguments: ['init' => 10],
            isSingleton: true
        );

        $c = (new DiContainerFactory())->make($d1 + $d2);

        $this->assertEquals(-10, $c->get(Sum::class)->add(-20));
        $this->assertEquals(20, $c->get(SumInterface::class)->add(-30));
    }
}
