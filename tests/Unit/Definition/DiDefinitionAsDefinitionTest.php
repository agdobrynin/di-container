<?php

declare(strict_types=1);

namespace Tests\Unit\Definition;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionSimple;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Definition\Fixtures\SimpleServiceWithArgument;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionSimple
 *
 * @internal
 */
class DiDefinitionAsDefinitionTest extends TestCase
{
    public function testDiDefinitionWithOutContainerKey(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        (new DiContainerFactory())->make([new DiDefinitionSimple([])]);
    }

    public function testDiDefinitionAsCallbackWithoutContainerKey(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        (new DiContainerFactory())->make([static fn () => 'a']);
    }

    public function testDiDefinitionWithContainerKeyEmptyString(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        (new DiContainerFactory())->make(['' => new DiDefinitionSimple([])]);
    }

    public function testDiDefinitionWithContainerKeyStringWithSpaces(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        (new DiContainerFactory())->make(['     ' => new DiDefinitionSimple([])]);
    }

    public function testDiDefinitionWithContainerKeyIsNumber(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        (new DiContainerFactory())->make([100 => new DiDefinitionSimple([])]);
    }

    public function testDiDefinitionSetKeyEmptyString(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        (new DiContainerFactory())->make()->set('', new DiDefinitionSimple([]));
    }

    public function testDiDefinitionSetKeyIsNumber(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        (new DiContainerFactory())->make()->set('  ', new DiDefinitionSimple([]));
    }

    public function testDiDefinitionAsDiDefinitionSimpleArray(): void
    {
        $container = (new DiContainerFactory())->make([
            'log' => new DiDefinitionSimple(['a' => 'aaa']),
        ]);

        $this->assertEquals(['a' => 'aaa'], $container->get('log'));
    }

    public function testDiDefinitionSetDiDefinitionSimpleArray(): void
    {
        $container = (new DiContainerFactory())->make();
        $container->set('log', new DiDefinitionSimple(['a' => 'aaa']));

        $this->assertEquals(['a' => 'aaa'], $container->get('log'));
    }

    public function testDiDefinitionAsDiDefinitionSimpleString(): void
    {
        $container = (new DiContainerFactory())->make([
            'x' => new DiDefinitionSimple('log'),
        ]);

        $this->assertEquals('log', $container->get('x'));
    }

    public function testDiDefinitionSetDiDefinitionSimpleString(): void
    {
        $container = (new DiContainerFactory())->make();
        $container->set('x', new DiDefinitionSimple('log'));

        $this->assertEquals('log', $container->get('x'));
    }

    public function testResolveDefinitionsWithoutAutowire(): void
    {
        $def = [
            'aaa' => 'aaa string',
            'null' => null,
            'log' => [DiContainerInterface::ARGUMENTS => ['x' => 'aaa']],
            'array_walk' => new DiDefinitionSimple(new \stdClass()),
        ];

        $container = new DiContainer($def, new DiContainerConfig(useAutowire: false, useAttribute: false));

        $this->assertEquals('aaa string', $container->get('aaa'));
        $this->assertNull($container->get('null'));
        $this->assertEquals(['arguments' => ['x' => 'aaa']], $container->get('log'));
        $this->assertInstanceOf(DiDefinitionSimple::class, $container->get('array_walk'));
        $this->assertInstanceOf(\stdClass::class, $container->get('array_walk')->getDefinition());
    }

    public function testResolveDefinitionAsDiDefinitionAutowire(): void
    {
        $definition = [
            new DiDefinitionAutowire(SimpleServiceWithArgument::class, true, arguments: ['token' => 'abc-abc']),
        ];

        $container = (new DiContainerFactory())->make($definition);
        $container->get(SimpleServiceWithArgument::class);
        $service = $container->get(SimpleServiceWithArgument::class);

        $this->assertInstanceOf(SimpleServiceWithArgument::class, $service);
        $this->assertSame($service, $container->get(SimpleServiceWithArgument::class));
        $this->assertEquals('abc-abc', $service->getToken());
    }

    public function testResolveDefinitionAsDiDefinitionAutowireWithReflectionClass(): void
    {
        $definition = [
            new DiDefinitionAutowire(new \ReflectionClass(SimpleServiceWithArgument::class), false, arguments: ['token' => 'abc-abc']),
        ];

        $container = (new DiContainerFactory())->make($definition);
        $container->get(SimpleServiceWithArgument::class);
        $service = $container->get(SimpleServiceWithArgument::class);

        $this->assertInstanceOf(SimpleServiceWithArgument::class, $service);
        $this->assertNotSame($service, $container->get(SimpleServiceWithArgument::class));
        $this->assertEquals('abc-abc', $service->getToken());
    }
}
