<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class CustomContainerIdentifierDefinitionTest extends TestCase
{
    public function testCustomContainerIdentifierDefinition(): void
    {
        $definitions = [
            'file-1' => diAutowire(\FilesystemIterator::class, isSingleton: true)
                ->addArgument('directory', __DIR__.'/Fixtures'),
            'file-2' => diAutowire(\FilesystemIterator::class, isSingleton: false)
                ->addArgument('directory', __DIR__.'/Fixtures'),
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $dir1 = $container->get('file-1');
        $this->assertTrue($dir1->valid());
        $this->assertSame($dir1, $container->get('file-1'));

        $files = \array_filter(
            \iterator_to_array($dir1),
            static fn (\SplFileInfo $item) => 'txt' === $item->getExtension()
        );

        $this->assertCount(2, $files);

        $dir2 = $container->get('file-2');
        $this->assertTrue($dir2->valid());
        $this->assertNotSame($dir2, $container->get('file-2'));

        $this->assertNotSame($dir1, $dir2);
    }
}
