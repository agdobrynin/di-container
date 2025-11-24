<?php

declare(strict_types=1);

namespace Tests\DiContainerCall;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\DiContainerCall\Fixtures\ClassWithSimplePublicProperty;
use Tests\DiContainerCall\Fixtures\Foo;

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
 * @covers \Kaspi\DiContainer\Helper
 * @covers \Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition
 * @covers \Kaspi\DiContainer\Traits\ContextExceptionTrait
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

        try {
            $container->call('\Tests\DiContainerCall\Fixtures\functionResolveArgumentByName');
        } catch (ContainerExceptionInterface $e) {
            self::assertInstanceOf(ArgumentBuilderExceptionInterface::class, $e);
            self::assertMatchesRegularExpression('/Cannot build argument via php attribute for Parameter #0 \[ <required> array \$allUsers ] in Function/', $e->getMessage());

            self::assertInstanceOf(AutowireExceptionInterface::class, $e->getPrevious());
            self::assertMatchesRegularExpression('/Cannot automatically resolve dependency in .+functionResolveArgumentByName\(\)\. Please specify the Parameter #0 \[ <required> array \$allUsers ]\./', $e->getPrevious()->getMessage());
        }
    }

    public function testUserFunctionInjectByAttributeFail(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter at position #1.+funcWithDependencyClass()/');

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
        $this->expectException(DiDefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter at position #0.+funcWithDependencyClass()/');

        (new DiContainer())->call('\Tests\DiContainerCall\Fixtures\funcWithDependencyClass');
    }

    public function testClosureSuccess(): void
    {
        $helper = static fn (Foo $foo) => $foo->baz;

        $container = new DiContainer(
            [
                diAutowire(Foo::class)
                    ->bindArguments('secure_string'),
            ],
            new DiContainerConfig(
                useAttribute: true
            )
        );

        self::assertEquals('secure_string', $container->call($helper));
    }

    public function testCallableWithClassAsObjectAndNoneStaticMethod(): void
    {
        $object = new ClassWithSimplePublicProperty('secure_string_one');

        $container = new DiContainer(
            config: new DiContainerConfig(
                useAttribute: true
            )
        );

        self::assertEquals('secure_string_one method foo', $container->call([$object, 'method'], ['foo']));
    }

    public function testCallableWithClassAsObjectAndInvokeMethod(): void
    {
        $object = new ClassWithSimplePublicProperty('secure_string_one');

        $container = new DiContainer(
            config: new DiContainerConfig(
                useAttribute: true
            )
        );

        self::assertEquals('secure_string_one invoke foo', $container->call($object, ['foo']));
    }

    public function testClosureFail(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter at position #0.+{closure.+()/');

        $helper = static fn (Foo $foo) => $foo->baz;

        (new DiContainer(config: new DiContainerConfig()))->call($helper);
    }

    public function testClassNotRegisteredInContainer(): void
    {
        $container = new DiContainer(
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: false
            )
        );

        $this->expectException(DiDefinitionCallableExceptionInterface::class);

        $container->call([Foo::class, 'bar'], ['ok']);
    }

    public function testClassButContainerIdentifierReturnNoneObject(): void
    {
        $container = new DiContainer(
            definitions: [
                Foo::class => 'aaaa',
            ],
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: false
            )
        );

        $this->expectException(DiDefinitionCallableExceptionInterface::class);

        $container->call([Foo::class, 'bar'], ['ok']);
    }
}
