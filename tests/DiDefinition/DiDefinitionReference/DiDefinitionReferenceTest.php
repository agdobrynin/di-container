<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionReference;

use Kaspi\DiContainer\DiDefinition\DiDefinitionReference;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 *
 * @internal
 */
class DiDefinitionReferenceTest extends TestCase
{
    public function dataProviderWrongDefinition(): \Generator
    {
        yield 'empty string' => [''];

        yield 'spaces string' => ['  '];
    }

    /**
     * @dataProvider dataProviderWrongDefinition
     */
    public function testDiDefinitionReferenceFail(string $definition): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        new DiDefinitionReference($definition);
    }

    public function dataProviderDefinitionSuccess(): \Generator
    {
        yield 'set 1' => ['key1', 'key1'];

        yield 'set 2' => ['   key2', '   key2'];

        yield 'set 3' => ['   key3   ', '   key3   '];

        yield 'set 4' => ['key4   ', 'key4   '];
    }

    /**
     * @dataProvider dataProviderDefinitionSuccess
     */
    public function testDiDefinitionReferenceSuccess(string $definition, string $expect): void
    {
        $this->assertEquals($expect, (new DiDefinitionReference($definition))->getDefinition());
    }
}
