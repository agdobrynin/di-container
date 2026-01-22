<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithDependency;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversNothing]
class DependencyOneTest extends TestCase
{
    public function testDependencyOne(): void
    {
        $definitions = static function () {
            // класс SplFileInfo создать единожды и всегда возвращать тот же объект
            yield diAutowire(SplFileInfo::class, isSingleton: true)
                // с аргументом $filename в конструкторе.
                ->bindArguments(filename: __FILE__)
            ;
        };

        $config = new DiContainerConfig();
        $builder = new DiContainerBuilder(containerConfig: $config);
        $builder->addDefinitions($definitions());
        $container = $builder->build();

        $class = $container->get(ClassWithDependency::class); // $splFileInfo-> getFilename() === this file name.
        self::assertEquals('file', $class->splFileInfo->getType());

        // получать один и тот же объект SplFileInfo так как в определении указан $isSingleton.
        $classTwo = $container->get(ClassWithDependency::class);

        self::assertSame($class->splFileInfo, $classTwo->splFileInfo);
    }
}
