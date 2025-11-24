<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\WrongDefinition;

use Generator;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Helper::functionName
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition
 * @covers \Kaspi\DiContainer\Traits\ContextExceptionTrait
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
    public function testWrongDefinitionAsString(array|callable|string $definition): void
    {
        $this->expectException(DiDefinitionCallableExceptionInterface::class);

        (new DiContainer(config: new DiContainerConfig()))->call($definition);
    }

    public function dataProviderWrongDefinition(): Generator
    {
        yield 'empty string' => [
            '',
        ];

        yield 'some random string' => [
            'service.ooo',
        ];

        yield 'empty array' => [
            [],
        ];

        yield 'one element provided in array' => [
            ['one'],
        ];

        yield 'empty array of array' => [
            [[], []],
        ];

        yield 'no exist class with method as string' => [
            'SomeClass::method',
        ];

        yield 'no exist class with method as array' => [
            ['SomeClass', 'method'],
        ];

        yield 'is not callable because method not exist' => [
            [self::class, 'method'],
        ];

        yield 'instance of object without method' => [
            [new stdClass(), 'method'],
        ];

        yield 'class and method is empty' => [
            '::',
        ];

        yield 'class is empty' => [
            '::method',
        ];

        yield 'method is empty' => [
            'class::',
        ];

        yield 'spaces with semicolon' => [
            '  ::  ',
        ];

        yield 'method with semicolon' => [
            'class::  ',
        ];
    }
}
