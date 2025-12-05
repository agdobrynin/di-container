<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithDependency;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithDependencyDiFactory;

use function Kaspi\DiContainer\diFactory;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diFactory')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionFactory::class)]
#[CoversClass(Helper::class)]
class DiFactoryTest extends TestCase
{
    public function testCreateByDiFactory(): void
    {
        $definitions = [
            ClassWithDependency::class => diFactory(ClassWithDependencyDiFactory::class, isSingleton: true),
        ];

        $container = (new DiContainerFactory())->make($definitions);

        $class = $container->get(ClassWithDependency::class);

        $this->assertInstanceOf(ClassWithDependency::class, $class);
        $this->assertInstanceOf(SplFileInfo::class, $class->splFileInfo);
        $this->assertEquals('file1.txt', $class->splFileInfo->getFilename());
        $this->assertSame($class, $container->get(ClassWithDependency::class));
    }

    public function testDiFactoryIdentifier(): void
    {
        $container = (new DiContainerFactory())->make([
            diFactory(ClassWithDependencyDiFactory::class, isSingleton: true),
        ]);

        self::assertInstanceOf(ClassWithDependency::class, $container->get(ClassWithDependencyDiFactory::class));
    }
}
