<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithDependency;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithDependencyDiFactory;

use function Kaspi\DiContainer\diFactory;

/**
 * @internal
 */
#[CoversNothing]
class DiFactoryTest extends TestCase
{
    public function testCreateByDiFactory(): void
    {
        $definitions = static function () {
            yield ClassWithDependency::class => diFactory(ClassWithDependencyDiFactory::class, isSingleton: true);
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions())
            ->build()
        ;

        $class = $container->get(ClassWithDependency::class);

        self::assertInstanceOf(ClassWithDependency::class, $class);
        self::assertInstanceOf(SplFileInfo::class, $class->splFileInfo);
        self::assertEquals('file1.txt', $class->splFileInfo->getFilename());
        self::assertSame($class, $container->get(ClassWithDependency::class));
    }

    public function testDiFactoryIdentifier(): void
    {
        $container = (new DiContainerBuilder())
            ->addDefinitions(
                (static function () {
                    yield diFactory(ClassWithDependencyDiFactory::class, isSingleton: true);
                })()
            )
            ->build()
        ;

        self::assertInstanceOf(ClassWithDependency::class, $container->get(ClassWithDependencyDiFactory::class));
    }
}
