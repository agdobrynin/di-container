<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\WrongDefinition;

use Generator;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use stdClass;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\functionName
 *
 * @internal
 */
class MainTest extends TestCase
{
    /**
     * @dataProvider dataProviderWrongDefinition
     *
     * @param mixed $definition
     */
    public function testWrongDefinitionAsString($definition, string $expectMessage): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage($expectMessage);

        (new DiContainer(config: new DiContainerConfig()))->call($definition);
    }

    public function dataProviderWrongDefinition(): Generator
    {
        yield 'empty string' => [
            '',
            'Definition is not callable',
        ];

        yield 'some random string' => [
            'service.ooo',
            'Definition is not callable. Got: type "string", value: \'service.ooo\'',
        ];

        yield 'empty array' => [
            [],
            'two array elements must be provided',
        ];

        yield 'empty array of array' => [
            [[], []],
            'Definition is not callable',
        ];

        yield 'no exist class with method as string' => [
            'SomeClass::method',
            'Definition is not callable. Got: type "string", value: \'SomeClass::method\'',
        ];

        yield 'no exist class with method as array' => [
            ['SomeClass', 'method'],
            'Definition is not callable. Got: type "array", value: array (',
        ];

        yield 'is not callable because method not exist' => [
            [self::class, 'method'],
            'Definition is not callable',
        ];

        yield 'instance of object without method' => [
            [new stdClass(), 'method'],
            'Definition is not callable',
        ];
    }
}
