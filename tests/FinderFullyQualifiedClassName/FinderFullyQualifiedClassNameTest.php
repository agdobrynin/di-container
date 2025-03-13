<?php

declare(strict_types=1);

namespace Tests\FinderFullyQualifiedClassName;

use FilesystemIterator;
use Generator;
use InvalidArgumentException;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SplFileInfo;

use function array_filter;
use function in_array;
use function sort;

use const ARRAY_FILTER_USE_KEY;
use const T_CLASS;
use const T_INTERFACE;

/**
 * @covers \Kaspi\DiContainer\Finder\FinderFullyQualifiedName
 *
 * @internal
 */
class FinderFullyQualifiedClassNameTest extends TestCase
{
    public static function dataProviderFinderClassConstructFail(): Generator
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectMessage);

        (new FinderFullyQualifiedName())->setNamespace($namespace)->setFiles([]);
    }

    public function testNeedSetNamespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Need set "namespace". Use method FinderFile::setNamespace().');

        (new FinderFullyQualifiedName())->find()->valid();
    }

    public function testNeedSetFiles(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Need set files for parsing. Use method FinderFile::setFiles().');

        (new FinderFullyQualifiedName())->setNamespace('App\\')->find()->valid();
    }

    public function testCannotOpenFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to open stream');

        (new FinderFullyQualifiedName())
            ->setNamespace('App\\')
            ->setFiles([new SplFileInfo('file-not-found.php')])
            ->find()
            ->valid()
        ;
    }

    public function testParsePhpException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot parse code');

        (new FinderFullyQualifiedName())
            ->setNamespace('App\\')
            ->setFiles([new SplFileInfo(__DIR__.'/Fixtures/Error/ParseError.php')])
            ->find()
            ->valid()
        ;
    }

    public function testGetClasses(): void
    {
        $dir = new FilesystemIterator(__DIR__.'/Fixtures/Success/');
        $fqNames = (new FinderFullyQualifiedName())
            ->setNamespace('Tests\\')
            ->setFiles($dir)
            ->find()
        ;

        $this->assertTrue($fqNames->valid());

        $foundFqn = [];

        foreach ($fqNames as $fqn) {
            $foundFqn[] = array_filter((array) $fqn, static fn (string $k) => in_array($k, ['fqn', 'tokenId'], true), ARRAY_FILTER_USE_KEY);
        }

        $expect = [
            ['fqn' => Fixtures\Success\TwoInOneOne::class, 'tokenId' => T_CLASS],
            ['fqn' => Fixtures\Success\TwoInOneTow::class, 'tokenId' => T_CLASS],
            ['fqn' => Fixtures\Success\WithTokenInterface::class, 'tokenId' => T_INTERFACE],
            ['fqn' => Fixtures\Success\ManyNamespaces::class, 'tokenId' => T_CLASS],
            ['fqn' => Fixtures\Success\SomeInterface::class, 'tokenId' => T_INTERFACE],
            ['fqn' => Fixtures\Success\Others\GetTokenInterface::class, 'tokenId' => T_INTERFACE],
            ['fqn' => Fixtures\Success\Others\ManyNamespaces::class, 'tokenId' => T_CLASS],
            ['fqn' => Fixtures\Success\One::class, 'tokenId' => T_CLASS],
            ['fqn' => Fixtures\Success\QueueInterface::class, 'tokenId' => T_INTERFACE],
        ];

        sort($expect);
        sort($foundFqn);

        $this->assertEquals($expect, $foundFqn);
    }
}
