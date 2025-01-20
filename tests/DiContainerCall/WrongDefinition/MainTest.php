<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\WrongDefinition;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait::isUseAttribute
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait::setUseAttribute
 *
 * @internal
 */
class MainTest extends TestCase
{
    public function dataProviderWrongDefinition(): \Generator
    {
        yield 'empty string' => [
            '',
            'Unresolvable dependency []',
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
            'Definition is not callable',
        ];

        yield 'instance of object without method' => [
            [new \stdClass(), 'method'],
            'Definition is not callable',
        ];
    }

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
}
