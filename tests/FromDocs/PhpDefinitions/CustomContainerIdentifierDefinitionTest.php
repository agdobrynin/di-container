<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use FilesystemIterator;
use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

use function array_filter;
use function iterator_to_array;
use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversNothing]
class CustomContainerIdentifierDefinitionTest extends TestCase
{
    public function testCustomContainerIdentifierDefinition(): void
    {
        $definitions = static function () {
            yield 'file-1' => diAutowire(FilesystemIterator::class, isSingleton: true)
                ->bindArguments(directory: __DIR__.'/Fixtures') // bind by name
            ;

            yield 'file-2' => diAutowire(FilesystemIterator::class, isSingleton: false)
                ->bindArguments(__DIR__.'/Fixtures') // bind by index
            ;
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions())
            ->build()
        ;

        $dir1 = $container->get('file-1');
        self::assertTrue($dir1->valid());
        self::assertSame($dir1, $container->get('file-1'));

        $files = array_filter(
            iterator_to_array($dir1),
            static fn (SplFileInfo $item) => 'txt' === $item->getExtension()
        );

        self::assertCount(2, $files);

        $dir2 = $container->get('file-2');

        self::assertTrue($dir2->valid());
        self::assertNotSame($dir2, $container->get('file-2'));
        self::assertNotSame($dir1, $dir2);
    }
}
