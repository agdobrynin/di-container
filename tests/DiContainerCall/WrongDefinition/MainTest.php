<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\WrongDefinition;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait;
use Kaspi\DiContainer\Traits\UseAttributeTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

/**
 * @internal
 */
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(UseAttributeTrait::class)]
#[CoversClass(ParameterTypeByReflectionTrait::class)]
class MainTest extends TestCase
{
    public static function dataProviderWrongDefinition(): \Generator
    {
        yield 'empty string' => [
            '',
            'Unresolvable dependency',
        ];

        yield 'some random string' => [
            'service.ooo',
            'Unresolvable dependency [service.ooo]',
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
            'Unresolvable dependency [SomeClass]',
        ];

        yield 'no exist class with method as array' => [
            ['SomeClass', 'method'],
            'Unresolvable dependency [SomeClass]',
        ];

        yield 'is not callable because method not exist' => [
            [self::class, 'method'],
            'Unresolvable dependency',
        ];

        yield 'instance of object without method' => [
            [new \stdClass(), 'method'],
            'Definition is not callable',
        ];
    }

    #[DataProvider('dataProviderWrongDefinition')]
    public function testWrongDefinitionAsString(mixed $definition, string $expectMessage): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage($expectMessage);

        (new DiContainer(config: new DiContainerConfig()))->call($definition);
    }
}
