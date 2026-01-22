<?php

declare(strict_types=1);

namespace Tests\DiDefinition;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionFactory::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionProxyClosure::class)]
#[CoversClass(DiDefinitionTaggedAs::class)]
#[CoversClass(DiDefinitionValue::class)]
class DiDefinitionTest extends TestCase
{
    #[DataProvider('definitionProvider')]
    public function testDefinitionIsDiDefinitionInterface($definition)
    {
        self::assertInstanceOf(DiDefinitionInterface::class, $definition);
    }

    public static function definitionProvider(): Generator
    {
        yield 'DiDefinitionAutowire' => [new DiDefinitionAutowire('foo')];

        yield 'DiDefinitionCallable' => [new DiDefinitionCallable('\log')];

        yield 'DiDefinitionFactory' => [new DiDefinitionFactory('foo')];

        yield 'DiDefinitionGet' => [new DiDefinitionGet('foo')];

        yield 'DiDefinitionProxyClosure' => [new DiDefinitionProxyClosure('foo')];

        yield 'DiDefinitionTaggedAs' => [new DiDefinitionTaggedAs('foo')];

        yield 'DiDefinitionValue' => [new DiDefinitionValue('foo')];
    }
}
