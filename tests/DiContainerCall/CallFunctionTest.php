<?php

declare(strict_types=1);

namespace Tests\DiContainerCall;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\DefinitionDiCall;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerNullConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\DiContainerCall\Fixtures\ClassWithSimplePublicProperty;
use Tests\DiContainerCall\Fixtures\Foo;

use function Kaspi\DiContainer\diAutowire;
use function round;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(Inject::class)]
#[CoversClass(DefinitionDiCall::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(Helper::class)]
#[CoversClass(ReflectionMethodByDefinition::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(DiContainerNullConfig::class)]
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
            self::assertMatchesRegularExpression('/Cannot build argument via php attribute for Parameter #0 \[ <required> array \$allUsers ] in .+functionResolveArgumentByName\(\)/', $e->getMessage());

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
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot build argument via type hint for Parameter #0.+funcWithDependencyClass()/');

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
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot get entry via container identifier');

        $container = new DiContainer(
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: false
            )
        );

        $container->call([Foo::class, 'bar'], ['ok']);
    }

    public function testClassButContainerIdentifierReturnNoneObject(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot create callable from array');

        $container = new DiContainer(
            definitions: [
                Foo::class => 'aaaa',
            ],
            config: new DiContainerConfig(
                useZeroConfigurationDefinition: false
            )
        );

        $container->call([Foo::class, 'bar'], ['ok']);
    }
}
