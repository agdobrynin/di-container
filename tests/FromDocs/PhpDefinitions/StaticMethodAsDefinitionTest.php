<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\ServiceLocation;

use function array_merge;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class StaticMethodAsDefinitionTest extends TestCase
{
    public function testStaticMethodAsDefinition(): void
    {
        $defServices = [
            diAutowire(ServiceLocation::class)
                ->bindArguments('Vice city'),
        ];

        // ... many definitions ...

        $defCustom = [
            // Статический метод класса является callable типом.
            // При вызове метода автоматически внедрится зависимость по типу ServiceLocation.
            'doSomething' => diCallable('Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithStaticMethods::doSomething'),
        ];

        $container = (new DiContainerFactory())->make(
            array_merge($defServices, $defCustom)
        );

        $res = $container->get('doSomething');
        $expect = (object) ['name' => 'John Doe', 'age' => 32, 'gender' => 'male', 'city' => 'Vice city'];

        $this->assertEquals($expect, $res);
    }
}
