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
            new \SplFileInfo('file-not-found.php'),
        ]))->getClasses()->valid();
    }

    public function testParsePhpException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot parse code');

        (new FinderClass('App\\', [
            new \SplFileInfo(__DIR__.'/Fixtures/Error/ParseError.php'),
        ]))->getClasses()->valid();
    }

    public function testGetClasses(): void
    {
        $dir = new \FilesystemIterator(__DIR__.'/Fixtures/Success/');
        $classes = (new FinderClass('Tests\\', $dir))->getClasses();

        $this->assertTrue($classes->valid());

        $foundClasses = [];

        foreach ($classes as $class) {
            $foundClasses[] = $class;
        }

        $this->assertEquals(
            [],
            \array_diff([
                TwoInOneOne::class,
                TwoInOneTow::class,
                ManyNamespaces::class,
                Fixtures\Success\Others\ManyNamespaces::class,
                One::class,
            ], $foundClasses)
        );
    }
}
