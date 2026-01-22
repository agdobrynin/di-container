<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\WrongDefinition;

use Generator;
use Kaspi\DiContainer\DefinitionDiCall;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
#[CoversClass(DefinitionDiCall::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(Helper::class)]
#[CoversClass(ReflectionMethodByDefinition::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
class MainTest extends TestCase
{
    /**
     * @param mixed $definition
     */
    #[DataProvider('dataProviderWrongDefinition')]
    public function testWrongDefinitionAsString(array|callable|string $definition): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/(Cannot create callable from)|(When the definition present is an array)/');

        (new DiContainer(config: new DiContainerConfig()))->call($definition);
    }

    public static function dataProviderWrongDefinition(): Generator
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
