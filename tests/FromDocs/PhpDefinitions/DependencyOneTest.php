<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithDependency;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class DependencyOneTest extends TestCase
{
    public function testDependencyOne(): void
    {
        $definitions = [
            // класс SplFileInfo создать единожды и всегда возвращать тот же объект
            diAutowire(\SplFileInfo::class, isSingleton: true)
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
