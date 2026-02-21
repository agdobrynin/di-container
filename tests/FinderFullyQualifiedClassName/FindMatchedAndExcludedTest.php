<?php

declare(strict_types=1);

namespace Tests\FinderFullyQualifiedClassName;

use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function array_column;

/**
 * @internal
 */
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
class FindMatchedAndExcludedTest extends TestCase
{
    public function testMatchedAndExcluded(): void
    {
        $finderFile = new FinderFile(
            __DIR__.'/Fixtures/SuccessAndExclude',
            [
                __DIR__.'/Fixtures/*/ExcludeDir/*',
                __DIR__.'/Fixtures/SuccessAndExclude/Baz.php',
            ],
        );

        $finderFQCN = new FinderFullyQualifiedName(
            'Tests\FinderFullyQualifiedClassName\Fixtures\\',
            $finderFile,
        );

        self::assertEquals(
            [
                'Tests\FinderFullyQualifiedClassName\Fixtures\SuccessAndExclude\Foo',
                'Tests\FinderFullyQualifiedClassName\Fixtures\SuccessAndExclude\SubDir\Qux',
            ],
            array_column([...$finderFQCN->getMatched()], 'fqn')
        );
        self::assertEquals(
            [
                'Tests\FinderFullyQualifiedClassName\Fixtures\SuccessAndExclude\Baz',
                'Tests\FinderFullyQualifiedClassName\Fixtures\SuccessAndExclude\ExcludeDir\Bar',
            ],
            array_column([...$finderFQCN->getExcluded()], 'fqn')
        );
    }
}
