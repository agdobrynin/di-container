<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Closure;
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\FromDocs\PhpAttribute\Fixtures\ClassWithHeavyDependency;

/**
 * @internal
 */
#[CoversNothing]
class DiProxyClosureTest extends TestCase
{
    public function testDiProxyClosure(): void
    {
        // use Attribute
        $container = (new DiContainerBuilder(
            containerConfig: new DiContainerConfig(useAttribute: true),
        ))
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
}
