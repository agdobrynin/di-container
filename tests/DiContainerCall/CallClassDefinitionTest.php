<?php

declare(strict_types=1);

namespace Tests\DiContainerCall;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DefinitionDiCall;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\DiContainerCall\Fixtures\ClassWithSimplePublicProperty;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversClass(AttributeReader::class)]
#[CoversClass(DefinitionDiCall::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(DiContainerFactory::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(Helper::class)]
#[CoversClass(ReflectionMethodByDefinition::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
class CallClassDefinitionTest extends TestCase
{
    public function testCallWithArgumentsInvokeClassWithoutPhpAttribute(): void
    {
        $config = new DiContainerConfig(useAttribute: false);
        $container = new DiContainer([
            diAutowire(ClassWithSimplePublicProperty::class)
                // bind by name
                ->bindArguments(publicProperty: 'Ready'),
        ], $config);

        $res = $container->call(ClassWithSimplePublicProperty::class, ...['append' => '🚀']);

        $this->assertEquals('Ready invoke 🚀', $res);
    }

    public function testCallInvokeClassArgumentDefaultValueWithoutPhpAttribute(): void
    {
        $config = new DiContainerConfig(useAttribute: false);
        $container = new DiContainer([
            diAutowire(ClassWithSimplePublicProperty::class)
                // bind by index
                ->bindArguments('Ready'),
        ], $config);

        $res = $container->call(ClassWithSimplePublicProperty::class);

        $this->assertEquals('Ready', $res);
    }

    public function testCallWithArgumentsClassWithNoneStaticMethodAsStringWithoutPhpAttribute(): void
    {
        $config = new DiContainerConfig(useAttribute: false);
        $container = new DiContainer([
            diAutowire(ClassWithSimplePublicProperty::class)
                ->bindArguments(publicProperty: 'Start'),
        ], $config);

        $res = $container->call(ClassWithSimplePublicProperty::class.'::method', ...['append' => '🚩']);

        $this->assertEquals('Start method 🚩', $res);
    }

    public function testCallWithArgumentsFromStaticMethodAsString(): void
    {
        $container = (new DiContainerFactory())->make();
        $res = $container->call(ClassWithSimplePublicProperty::class.'::staticMethod', ...['append' => '🗿']);

        $this->assertEquals('static method 🗿', $res);
    }
}
