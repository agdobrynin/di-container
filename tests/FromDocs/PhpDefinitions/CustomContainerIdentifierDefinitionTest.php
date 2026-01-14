<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use FilesystemIterator;
use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

use function array_filter;
use function iterator_to_array;
use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversFunction('Kaspi\DiContainer\diAutowire')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(Helper::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
class CustomContainerIdentifierDefinitionTest extends TestCase
{
    public function testCustomContainerIdentifierDefinition(): void
    {
        $definitions = [
            'file-1' => diAutowire(FilesystemIterator::class, isSingleton: true)
                ->bindArguments(directory: __DIR__.'/Fixtures'), // bind by name
            'file-2' => diAutowire(FilesystemIterator::class, isSingleton: false)
                ->bindArguments(__DIR__.'/Fixtures'), // bind by index
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $dir1 = $container->get('file-1');
        $this->assertTrue($dir1->valid());
        $this->assertSame($dir1, $container->get('file-1'));

        $files = array_filter(
            iterator_to_array($dir1),
            static fn (SplFileInfo $item) => 'txt' === $item->getExtension()
        );

        $this->assertCount(2, $files);

        $dir2 = $container->get('file-2');
        $this->assertTrue($dir2->valid());
        $this->assertNotSame($dir2, $container->get('file-2'));

        $this->assertNotSame($dir1, $dir2);
    }
}
