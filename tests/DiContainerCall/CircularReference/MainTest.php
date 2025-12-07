<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\CircularReference;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\DefinitionDiCall;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\DiContainerCall\CircularReference\Fixtures\ClassWithMethod;
use Tests\DiContainerCall\CircularReference\Fixtures\FirstClass;
use Tests\DiContainerCall\CircularReference\Fixtures\SecondClass;
use Tests\DiContainerCall\CircularReference\Fixtures\ThirdClass;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversFunction('\Kaspi\DiContainer\diTaggedAs')]
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
#[CoversClass(DiDefinitionTaggedAs::class)]
#[CoversClass(Helper::class)]
#[CoversClass(ReflectionMethodByDefinition::class)]
#[CoversClass(CallCircularDependencyException::class)]
class MainTest extends TestCase
{
    public function testCircularInjectByAttribute(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot get entry via container identifier ".+FirstClass" for create callable definition\./');

        $definitions = [
            diAutowire(FirstClass::class),
            'services.second' => diAutowire(SecondClass::class),
            'services.third' => diAutowire(ThirdClass::class),
        ];

        $container = new DiContainer($definitions, new DiContainerConfig(useZeroConfigurationDefinition: false));

        $container->call(FirstClass::class);
    }

    public function testCircularByInjectInMethod(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter by named argument \$service.+ClassWithMethod::method\(\)/');

        $definitions = [
            diAutowire(FirstClass::class),
            'services.second' => diAutowire(SecondClass::class),
            'services.third' => diAutowire(ThirdClass::class),
            diAutowire(ClassWithMethod::class),
        ];

        $container = new DiContainer($definitions, new DiContainerConfig(useZeroConfigurationDefinition: false));

        $container->call([ClassWithMethod::class, 'method'], ['service' => diGet('services.second')]);
    }

    public function testCircularWithoutAttribute(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot get entry via container identifier ".+FirstClass" for create callable definition\./');

        $definitions = [
            diAutowire(FirstClass::class),
            diAutowire(SecondClass::class),
            diAutowire(ThirdClass::class),
        ];

        $config = new DiContainerConfig(useZeroConfigurationDefinition: false, useAttribute: false);
        $container = new DiContainer($definitions, $config);

        $container->call(FirstClass::class);
    }

    public function testCircularInMethodWithoutAttribute(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter by named argument \$service.+ClassWithMethod::method\(\)\./');

        $definitions = [
            diAutowire(FirstClass::class),
            diAutowire(SecondClass::class),
            diAutowire(ThirdClass::class),
            diAutowire(ClassWithMethod::class),
        ];

        $config = new DiContainerConfig(useZeroConfigurationDefinition: false, useAttribute: false);
        $container = new DiContainer($definitions, $config);

        $container->call([ClassWithMethod::class, 'method'], ['service' => diAutowire(ThirdClass::class)]);
    }

    public function testCircularInMethodByTaggedAsWithoutAttribute(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter by named argument \$service.+ClassWithMethod::method\(\)\./');

        $definitions = [
            diAutowire(FirstClass::class),
            diAutowire(SecondClass::class),
            diAutowire(ThirdClass::class)
                ->bindTag('tag-one'),
            diAutowire(ClassWithMethod::class),
        ];

        $config = new DiContainerConfig(useZeroConfigurationDefinition: false, useAttribute: false);
        $container = new DiContainer($definitions, $config);

        $container->call([ClassWithMethod::class, 'method'], ['service' => diTaggedAs('tag-one', false)]);
    }
}
