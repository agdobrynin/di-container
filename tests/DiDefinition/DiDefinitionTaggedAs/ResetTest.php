<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs;

use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedObjectDefinitionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DiDefinitionTaggedAs::class)]
class ResetTest extends TestCase
{
    public function testReset(): void
    {
        $bar = $this->createMock(DiTaggedObjectDefinitionInterface::class);
        $bar->method('getDefinitionIdentifier')
            ->willReturn('bar')
        ;

        $foo = $this->createMock(DiTaggedObjectDefinitionInterface::class);
        $foo->method('getDefinitionIdentifier')
            ->willReturn('foo')
        ;

        $container = $this->createMock(DiContainerInterface::class);
        $container->method('findTaggedDefinitions')
            ->willReturnCallback(static function () use ($foo, $bar) {
                yield 'foo' => $foo;

                yield 'bar' => $bar;
            })
        ;

        $taggedAs = (new DiDefinitionTaggedAs('foo', selfExclude: true));
        $ids = $taggedAs->exposeContainerIdentifiers($container, $foo);

        self::assertSame(['bar' => 'bar'], $ids);

        $taggedAs->reset();

        $ids = $taggedAs->exposeContainerIdentifiers($container, $bar);

        self::assertSame(['foo' => 'foo'], $ids);
    }
}
