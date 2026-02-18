<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader;

use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\FinderFullyQualifiedNameCollection;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\DefinitionsLoader\Fixtures\DefinitionsConfigurator\Bar;
use Tests\DefinitionsLoader\Fixtures\DefinitionsConfigurator\Foo;

/**
 * @internal
 */
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(FinderFullyQualifiedNameCollection::class)]
#[CoversClass(FinderFile::class)]
#[CoversClass(FinderFullyQualifiedName::class)]
#[CoversClass(Helper::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
class DefinitionsLoaderWithConfiguratorTest extends TestCase
{
    public function testCircularLoadFromFile(): void
    {
        $this->expectException(DefinitionsLoaderExceptionInterface::class);

        vfsStream::setup(structure: [
            'config1.php' => '<?php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (DefinitionsConfiguratorInterface $configurator) {
    yield "foo" => "baz";
    
    $configurator->load("vfs://root/config1.php");
};',
        ]);

        (new DefinitionsLoader())
            ->load(vfsStream::url('root/config1.php'))
            ->definitions()
            ->valid()
        ;
    }

    public function testFromFileWithoutReturn(): void
    {
        vfsStream::setup(structure: [
            'config1.php' => '<?php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (DefinitionsConfiguratorInterface $configurator) {
    // set definition and override existing definition
    $configurator->setDefinition("foo", "baz");
};',
        ]);

        $defs = (new DefinitionsLoader())
            ->addDefinitions(false, ['foo' => 'qux'])
            ->load(vfsStream::url('root/config1.php'))
            ->definitions()
        ;

        self::assertEquals(['foo' => 'baz'], [$defs->key() => $defs->current()]);
    }

    public function testNotImportRemovedDefinition(): void
    {
        vfsStream::setup(structure: [
            'config1.php' => '<?php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Tests\DefinitionsLoader\Fixtures\RemoveDefinition\Bar;
        
return static function (DefinitionsConfiguratorInterface $configurator) {
    $configurator->removeDefinition(Bar::class);
};',
        ]);

        $defs = (new DefinitionsLoader())
            ->import('Tests\DefinitionsLoader\Fixtures\RemoveDefinition\\', __DIR__.'/Fixtures/RemoveDefinition/')
            ->load(vfsStream::url('root/config1.php'))
            ->definitions()
        ;

        self::assertEmpty([...$defs]);
    }

    public function testLoadOverrideInConfigurator(): void
    {
        vfsStream::setup(structure: [
            'config1.php' => '<?php
use Tests\DefinitionsLoader\Fixtures\RemoveDefinition\Bar;

return [
    \Kaspi\DiContainer\diAutowire(Bar::class),
];',
            'config2.php' => '<?php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
        
return static function (DefinitionsConfiguratorInterface $configurator) {
    $configurator->loadOverride("vfs://root/config1.php");
};',
        ]);

        $defs = (new DefinitionsLoader())
            ->useAttribute(false)
            ->import('Tests\DefinitionsLoader\Fixtures\RemoveDefinition\\', __DIR__.'/Fixtures/RemoveDefinition/')
            ->load(vfsStream::url('root/config1.php'))
            ->load(vfsStream::url('root/config2.php'))
            ->definitions()
        ;

        self::assertEquals('Tests\DefinitionsLoader\Fixtures\RemoveDefinition\Bar', $defs->key());

        $defs->next();

        self::assertFalse($defs->valid());
    }

    public function testGetDefinitionInConfiguratorNotFound(): void
    {
        vfsStream::setup(structure: [
            'config1.php' => '<?php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
        
return static function (DefinitionsConfiguratorInterface $configurator) {
    $qux = $configurator->getDefinition("services.qux");

    if (null === $qux) {
        $configurator->setDefinition("services.qux", "qux val");
    }
};',
        ]);

        $defs = (new DefinitionsLoader())
            ->useAttribute(false)
            ->load(vfsStream::url('root/config1.php'))
            ->definitions()
        ;

        /** @var DiDefinitionAutowire $def */
        foreach ($defs as $id => $def) {
            if ('services.qux' === $id) {
                self::assertEquals('qux val', $def);
            }
        }
    }

    public function testGetDefinitionInConfiguratorAndConfigure(): void
    {
        vfsStream::setup(structure: [
            'config1.php' => '<?php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Tests\DefinitionsLoader\Fixtures\DefinitionsConfigurator\Foo;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
        
return static function (DefinitionsConfiguratorInterface $configurator) {
    $foo = $configurator->getDefinition(Foo::class);
    if ($foo instanceof DiDefinitionAutowire) {
        $foo->bindTag("tags.foo");    
    }
};',
        ]);

        $defs = (new DefinitionsLoader())
            ->useAttribute(false)
            ->import('Tests\DefinitionsLoader\Fixtures\DefinitionsConfigurator\\', __DIR__.'/Fixtures/DefinitionsConfigurator/')
            ->load(vfsStream::url('root/config1.php'))
            ->definitions()
        ;

        /** @var DiDefinitionAutowire $def */
        foreach ($defs as $id => $def) {
            if (Bar::class === $id) {
                self::assertFalse(isset($def->getBoundTags()['tags.foo']));
            }

            if (Foo::class === $id) {
                self::assertTrue(isset($def->getBoundTags()['tags.foo']));
            }
        }
    }
}
