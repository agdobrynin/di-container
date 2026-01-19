<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Closure;
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\FromDocs\PhpDefinitions\Fixtures\ClassWithHeavyDependency;
use Tests\FromDocs\PhpDefinitions\Fixtures\HeavyDependency;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diProxyClosure;

/**
 * @internal
 */
#[CoversNothing]
class DiProxyClosureTest extends TestCase
{
    public function testDiProxyClosure(): void
    {
        $definition = static function () {
            yield diAutowire(ClassWithHeavyDependency::class)
                ->bindArguments(
                    heavyDependency: diProxyClosure(HeavyDependency::class),
                )
            ;
        };

        // Not use Attribute
        $container = (new DiContainerBuilder(
            containerConfig: new DiContainerConfig(useAttribute: false),
        ))
            ->addDefinitions($definition())
            ->build()
        ;

        // свойство ClassWithHeavyDependency::$heavyDependency
        // ещё не инициализировано.
        $someClass = $container->get(ClassWithHeavyDependency::class);

        self::assertInstanceOf(ClassWithHeavyDependency::class, $someClass);

        self::assertEquals(
            Closure::class,
            (new ReflectionProperty($someClass, 'heavyDependency'))->getType()->getName()
        );

        self::assertEquals('doMake in LiteDependency', $someClass->doLiteDependency());

        // Внутри метода инициализируется
        // свойство ClassWithHeavyDependency::$heavyDependency
        // через Closure вызов (callback функция)
        self::assertEquals('doMake in HeavyDependency', $someClass->doHeavyDependency());
    }

    public function testProxyClosureAsDefinition(): void
    {
        $definition = static function () {
            yield 'service-one' => diProxyClosure(HeavyDependency::class);
        };

        $container = (new DiContainerBuilder(
            containerConfig: new DiContainerConfig(useAttribute: false)
        ))
            ->addDefinitions($definition())
            ->build()
        ;

        self::assertInstanceOf(Closure::class, $container->get('service-one'));
        self::assertInstanceOf(HeavyDependency::class, $container->get('service-one')());
    }
}
