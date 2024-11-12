<?php

declare(strict_types=1);

namespace Tests\Unit\Definition;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionSimple;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
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
}
