<?php

declare(strict_types=1);

namespace Tests\DiContainerCall;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\DiContainerCall\Fixtures\ClassWithSimplePublicProperty;

use function Kaspi\DiContainer\diAutowire;
use function round;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\functionName
 * @covers \Kaspi\DiContainer\Traits\ArgumentResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 *
 * @internal
 */
class CallFunctionTest extends TestCase
{
    public function testBuiltinFunction(): void
    {
        $container = new DiContainer();
        $res = round($container->call('log', ['num' => 10]));

        $this->assertEquals(2.0, $res);
    }

    public function testUserFunction(): void
    {
        $definitions = [
            diAutowire(ClassWithSimplePublicProperty::class)
                // bind by name
                ->bindArguments(publicProperty: 'Ready'),
        ];

        $container = new DiContainer($definitions);

        $res = $container->call('\Tests\DiContainerCall\Fixtures\funcWithDependencyClass', ['append' => '🚀']);

        $this->assertEquals('Ready + 🚀', $res);
    }

    public function testUserFunctionWithDefaultValue(): void
    {
        $container = new DiContainer([
            diAutowire(ClassWithSimplePublicProperty::class)
                // bind by index
                ->bindArguments('I am alone'),
        ]);

        $res = $container->call('\Tests\DiContainerCall\Fixtures\funcWithDependencyClass');

        $this->assertEquals('I am alone', $res);
    }

    public function testUserFunctionResolveArgumentByNameException(): void
    {
        $definitions = [
            'allUsers' => ['Ivan', 'Piter', 'Vasiliy'],
        ];
        $config = new DiContainerConfig();
        $container = new DiContainer($definitions, $config);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot automatically resolve dependency.+array \$allUsers/');

        $res = $container->call('\Tests\DiContainerCall\Fixtures\functionResolveArgumentByName');
    }

    public function testUserFunctionInjectByAttributeFail(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Unresolvable dependency "service\.append"/');

        $definitions = [
            diAutowire(ClassWithSimplePublicProperty::class),
            'vars.public-property' => 'Hello',
        ];

        $config = new DiContainerConfig(useAttribute: true);
        $container = new DiContainer($definitions, $config);

        $container->call('\Tests\DiContainerCall\Fixtures\funcWithDependencyClass');
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
        $this->expectExceptionMessageMatches('/Unresolvable dependency.+ClassWithSimplePublicProperty/');

        $container->call('\Tests\DiContainerCall\Fixtures\funcWithDependencyClass');
    }
}
