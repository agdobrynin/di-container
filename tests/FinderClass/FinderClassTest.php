<?php

declare(strict_types=1);

namespace Tests\FinderClass;

use Kaspi\DiContainer\Finder\FinderClass;
use PHPUnit\Framework\TestCase;
use Tests\FinderClass\Fixtures\Success\ManyNamespaces;
use Tests\FinderClass\Fixtures\Success\One;
use Tests\FinderClass\Fixtures\Success\TwoInOneOne;
use Tests\FinderClass\Fixtures\Success\TwoInOneTow;

/**
 * @covers \Kaspi\DiContainer\Finder\FinderClass
 *
 * @internal
 */
class FinderClassTest extends TestCase
{
    public static function dataProviderFinderClassConstructFail(): \Generator
    {
        yield 'empty string' => [
            '',
            'Argument $namespace must be end with symbol "\"',
        ];

        yield 'invalid namespace #1' => [
            '11App\\',
            'Argument $namespace must be compatible with PSR-4',
        ];

        yield 'invalid namespace #2' => [
            '   App\\',
            'Argument $namespace must be compatible with PSR-4',
        ];

        yield 'invalid namespace #3' => [
            '\\',
            'Argument $namespace must be compatible with PSR-4',
        ];
    }

    /**
     * @dataProvider dataProviderFinderClassConstructFail
     */
    public function testFinderClassConstructFail(string $namespace, string $expectMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectMessage);

        new FinderClass($namespace, []);
    }

    public function testCannotOpenFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot get file contents from "file-not-found.php"');

        (new FinderClass('App\\', [
            'file-not-found.php',
        ]))->getClasses()->valid();
    }

    public function testParsePhpException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot parse code');

        (new FinderClass('App\\', [
            __DIR__.'/Fixtures/Error/ParseError.php',
        ]))->getClasses()->valid();
    }

    public function testGetClasses(): void
    {
        $classes = (new FinderClass('Tests\\', [
            __DIR__.'/Fixtures/Success/function.php',
            __DIR__.'/Fixtures/Success/function2.php',
            __DIR__.'/Fixtures/Success/ManyInOne.php',
            __DIR__.'/Fixtures/Success/ManyNamespaces.php',
            __DIR__.'/Fixtures/Success/MyTrait.php',
            __DIR__.'/Fixtures/Success/One.php',
        ]))->getClasses();

        $this->assertTrue($classes->valid());

        $this->assertEquals(TwoInOneOne::class, $classes->current());
        $classes->next();
        $this->assertEquals(TwoInOneTow::class, $classes->current());
        $classes->next();
        $this->assertEquals(ManyNamespaces::class, $classes->current());
        $classes->next();
        $this->assertEquals(Fixtures\Success\Others\ManyNamespaces::class, $classes->current());
        $classes->next();
        $this->assertEquals(One::class, $classes->current());
        $classes->next();
        $this->assertFalse($classes->valid());
    }
}
