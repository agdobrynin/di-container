<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithDependency;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(Helper::class)]
class DependencyOneTest extends TestCase
{
    public function testDependencyOne(): void
    {
        $definitions = [
            // класс SplFileInfo создать единожды и всегда возвращать тот же объект
            diAutowire(SplFileInfo::class, isSingleton: true)
                // с аргументом $filename в конструкторе.
                ->bindArguments(filename: __FILE__),
        ];

        $config = new DiContainerConfig();
        $container = new DiContainer(definitions: $definitions, config: $config);

        $class = $container->get(ClassWithDependency::class); // $splFileInfo-> getFilename() === this file name.
        $this->assertEquals('file', $class->splFileInfo->getType());

        // получать один и тот же объект SplFileInfo так как в определении указан $isSingleton.
        $classTwo = $container->get(ClassWithDependency::class);
        $this->assertSame($class->splFileInfo, $classTwo->splFileInfo);
    }
}
