<?php

declare(strict_types=1);

namespace Tests\FinderFullyQualifiedClassName;

use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use PHPUnit\Framework\TestCase;
use Tests\FinderFullyQualifiedClassName\Fixtures\Success\ManyNamespaces;
use Tests\FinderFullyQualifiedClassName\Fixtures\Success\One;
use Tests\FinderFullyQualifiedClassName\Fixtures\Success\TwoInOneOne;
use Tests\FinderFullyQualifiedClassName\Fixtures\Success\TwoInOneTow;

/**
 * @covers \Kaspi\DiContainer\Finder\FinderFullyQualifiedName
 *
 * @internal
 */
class FinderFullyQualifiedClassNameTest extends TestCase
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

        new FinderFullyQualifiedName($namespace, []);
    }

    public function testCannotOpenFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to open stream');

        (new FinderFullyQualifiedName('App\\', [
            new \SplFileInfo('file-not-found.php'),
        ]))->find()->valid();
    }

    public function testParsePhpException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot parse code');

        (new FinderFullyQualifiedName('App\\', [
            new \SplFileInfo(__DIR__.'/Fixtures/Error/ParseError.php'),
        ]))->find()->valid();
    }

    public function testGetClasses(): void
    {
        $dir = new \FilesystemIterator(__DIR__.'/Fixtures/Success/');
        $classes = (new FinderFullyQualifiedName('Tests\\', $dir))->find();

        $this->assertTrue($classes->valid());

        $foundClasses = [];

        foreach ($classes as $class) {
            $foundClasses[] = $class;
        }
        $expect = [
            TwoInOneOne::class,
            TwoInOneTow::class,
            Fixtures\Success\WithTokenInterface::class,
            ManyNamespaces::class,
            Fixtures\Success\SomeInterface::class,
            Fixtures\Success\Others\GetTokenInterface::class,
            Fixtures\Success\Others\ManyNamespaces::class,
            One::class,
            Fixtures\Success\QueueInterface::class,
        ];

        \sort($expect);
        \sort($foundClasses);

        $this->assertEquals($expect, $foundClasses);
    }
}
