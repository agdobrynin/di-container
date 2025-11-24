<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithDependency;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithDependencyDiFactory;

use function Kaspi\DiContainer\diFactory;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionFactory
 * @covers \Kaspi\DiContainer\diFactory
 * @covers \Kaspi\DiContainer\Helper
 *
 * @internal
 */
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
