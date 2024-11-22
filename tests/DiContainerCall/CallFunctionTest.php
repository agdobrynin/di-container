<?php

declare(strict_types=1);

namespace Tests\DiContainerCall;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\ClassWithSimplePublicProperty;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 *
 * @internal
 */
class CallFunctionTest extends TestCase
{
    public function testBuiltinFunction(): void
    {
        $container = (new DiContainerFactory())->make();
        $res = \round($container->call('log', ['num' => 10]));

        $this->assertEquals(2.0, $res);
    }

    /**
     * @covers \Kaspi\DiContainer\diAutowire
     */
    public function testUserFunction(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(ClassWithSimplePublicProperty::class)
                ->addArgument('publicProperty', 'Ready'),
        ]);

        $res = $container->call('\Tests\Fixtures\funcWithDependencyClass', ['append' => '🚀']);

        $this->assertEquals('Ready + 🚀', $res);
    }

    /**
     * @covers \Kaspi\DiContainer\diAutowire
     */
    public function testUserFunctionWithDefaultValue(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(ClassWithSimplePublicProperty::class)
                ->addArgument('publicProperty', 'I am alone'),
        ]);

        $res = $container->call('\Tests\Fixtures\funcWithDependencyClass');

        $this->assertEquals('I am alone', $res);
    }

    public function testUserFunctionVariadicArguments() {}
}
