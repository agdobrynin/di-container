<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassFirst;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassInterface;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;

/**
 * @internal
 */
#[CoversNothing]
class ResolveByInterfaceTest extends TestCase
{
    public function testResolveByClass(): void
    {
        $definition = static function () {
            yield ClassInterface::class => diAutowire(ClassFirst::class)
                // bind by name
                ->bindArguments(file: '/var/log/app.log')
            ;
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definition())
            ->build()
        ;

        self::assertEquals('/var/log/app.log', $container->get(ClassInterface::class)->file);
    }

    public function testResolveByByReference(): void
    {
        $classesDefinitions = static function () {
            yield diAutowire(ClassFirst::class)
                // bind by index
                ->bindArguments('/var/log/app.log')
            ;
        };

        // ... many definitions ...

        $interfacesDefinitions = static function () {
            yield ClassInterface::class => diGet(ClassFirst::class);
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($classesDefinitions())
            ->addDefinitions($interfacesDefinitions())
            ->build()
        ;

        self::assertEquals('/var/log/app.log', $container->get(ClassInterface::class)->file);
    }
}
