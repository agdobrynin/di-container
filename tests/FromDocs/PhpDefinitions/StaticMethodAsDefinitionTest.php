<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\ServiceLocation;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;

/**
 * @internal
 */
#[CoversNothing]
class StaticMethodAsDefinitionTest extends TestCase
{
    public function testStaticMethodAsDefinition(): void
    {
        $defServices = static function () {
            yield diAutowire(ServiceLocation::class)
                ->bindArguments('Vice city')
            ;
        };

        // ... many definitions ...

        $defCustom = static function () {
            // Статический метод класса является callable типом.
            // При вызове метода автоматически внедрится зависимость по типу ServiceLocation.
            yield 'doSomething' => diCallable('Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithStaticMethods::doSomething');
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($defServices())
            ->addDefinitions($defCustom())
            ->build()
        ;

        $res = $container->get('doSomething');
        $expect = (object) ['name' => 'John Doe', 'age' => 32, 'gender' => 'male', 'city' => 'Vice city'];

        self::assertEquals($expect, $res);
    }
}
