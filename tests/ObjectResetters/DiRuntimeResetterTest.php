<?php

declare(strict_types=1);

namespace Tests\ObjectResetters;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionRuntime;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\FreezeTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\ObjectResetters\Fixtures\Foo;
use Tests\ObjectResetters\Fixtures\FooResetter;

/**
 * @internal
 */
#[CoversClass(DiDefinitionRuntime::class)]
#[CoversClass(TagsTrait::class)]
#[CoversClass(FreezeTrait::class)]
class DiRuntimeResetterTest extends TestCase
{
    public function testResetterOnFrozenDefinition(): void
    {
        $definition = (new DiDefinitionRuntime(Foo::class))
            ->setResetter('flush')
        ;

        $definition->freeze();

        self::assertEquals('flush', $definition->getResetter());

        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/^Cannot call.+DiDefinitionRuntime::setResetter\(\) on a frozen definition/');

        $definition->setResetter('\Tests\ObjectResetters\Fixtures\Foo\resetFoo');
    }

    public function testResetterNotDefined(): void
    {
        $definition = new DiDefinitionRuntime(Foo::class);

        self::assertFalse($definition->getResetter());
    }

    #[DataProvider('dataProviderSetResetter')]
    public function testSetResetter(string $definition, callable|string $resetter): void
    {
        $definition = (new DiDefinitionRuntime($definition))
            ->setResetter($resetter)
        ;

        self::assertSame($resetter, $definition->getResetter());
    }

    public static function dataProviderSetResetter(): Generator
    {
        yield 'as method string' => [
            Foo::class,
            'flush',
        ];

        yield 'as Closure' => [
            Foo::class,
            static function (Foo $foo): void {
                $foo->foo = 'fn';
            },
        ];

        yield 'as callable through static method' => [
            Foo::class,
            [FooResetter::class, 'doReset'],
        ];

        yield 'as callable through function' => [
            Foo::class,
            '\Tests\ObjectResetters\Fixtures\resetFoo',
        ];
    }
}
