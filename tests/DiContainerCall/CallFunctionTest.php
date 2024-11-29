<?php

declare(strict_types=1);

namespace Tests\DiContainerCall;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Tests\DiContainerCall\Fixtures\ClassWithSimplePublicProperty;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait::isUseAttribute
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait::setUseAttribute
 *
 * @internal
 */
class CallFunctionTest extends TestCase
{
    public function testBuiltinFunction(): void
    {
        $container = new DiContainer();
        $res = \round($container->call('log', ['num' => 10]));

        $this->assertEquals(2.0, $res);
    }

    public function testUserFunction(): void
    {
        $definitions = [
            diAutowire(ClassWithSimplePublicProperty::class)
                ->addArgument('publicProperty', 'Ready'),
        ];

        $container = new DiContainer($definitions);

        $res = $container->call('\Tests\DiContainerCall\Fixtures\funcWithDependencyClass', ['append' => '🚀']);

        $this->assertEquals('Ready + 🚀', $res);
    }

    public function testUserFunctionWithDefaultValue(): void
    {
        $container = new DiContainer([
            diAutowire(ClassWithSimplePublicProperty::class)
                ->addArgument('publicProperty', 'I am alone'),
        ]);

        $res = $container->call('\Tests\DiContainerCall\Fixtures\funcWithDependencyClass');

        $this->assertEquals('I am alone', $res);
    }

    public function testUserFunctionResolveArgumentByName(): void
    {
        $definitions = [
            'allUsers' => ['Ivan', 'Piter', 'Vasiliy'],
        ];
        $config = new DiContainerConfig();
        $container = new DiContainer($definitions, $config);

        $res = $container->call('\Tests\DiContainerCall\Fixtures\functionResolveArgumentByName');
        $expect = 'IVAN - PITER - VASILIY';

        $this->assertEquals($expect, $res);
    }

    public function testUserFunctionInjectByAttributeWithDefaultValue(): void
    {
        $definitions = [
            diAutowire(ClassWithSimplePublicProperty::class),
            'vars.public-property' => 'Hello',
        ];

        $config = new DiContainerConfig(useAttribute: true);
        $container = new DiContainer($definitions, $config);

        $res = $container->call('\Tests\DiContainerCall\Fixtures\funcWithDependencyClass');

        $this->assertEquals('Hello', $res);
    }

    public function testUserFunctionInjectByAttribute(): void
    {
        $definitions = [
            diAutowire(ClassWithSimplePublicProperty::class),
            'vars.public-property' => 'Hello',
            'service.append' => '🌐',
        ];

        $config = new DiContainerConfig(useAttribute: true);
        $container = new DiContainer($definitions, $config);

        $res = $container->call('\Tests\DiContainerCall\Fixtures\funcWithDependencyClass');

        $this->assertEquals('Hello + 🌐', $res);
    }

    public function testUserFunctionUnresolvedArgument(): void
    {
        $container = new DiContainer();

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Unresolvable dependency.+ClassWithSimplePublicProperty \$class/');

        $container->call('\Tests\DiContainerCall\Fixtures\funcWithDependencyClass');
    }
}
