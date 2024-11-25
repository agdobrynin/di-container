<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\CircularReference;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Exception\CallCircularDependencyException;
use PHPUnit\Framework\TestCase;
use Tests\DiContainerCall\CircularReference\Fixtures\ClassWithMethod;
use Tests\DiContainerCall\CircularReference\Fixtures\FirstClass;
use Tests\DiContainerCall\CircularReference\Fixtures\SecondClass;
use Tests\DiContainerCall\CircularReference\Fixtures\ThirdClass;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait::isUseAttribute
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait::setUseAttribute
 *
 * @internal
 */
class MainTest extends TestCase
{
    public function testCircularInjectByAttribute(): void
    {
        $definitions = [
            diAutowire(FirstClass::class),
            'services.second' => diAutowire(SecondClass::class),
            'services.third' => diAutowire(ThirdClass::class),
        ];

        $container = new DiContainer($definitions, new DiContainerConfig(useZeroConfigurationDefinition: false));

        $this->expectException(CallCircularDependencyException::class);
        $this->expectExceptionMessageMatches(
            '/cyclical dependency.+FirstClass.+services\.second.+services\.third.+FirstClass/'
        );

        $container->call(FirstClass::class);
    }

    public function testCircularByInjectInMethod(): void
    {
        $definitions = [
            diAutowire(FirstClass::class),
            'services.second' => diAutowire(SecondClass::class),
            'services.third' => diAutowire(ThirdClass::class),
            diAutowire(ClassWithMethod::class),
        ];

        $container = new DiContainer($definitions, new DiContainerConfig(useZeroConfigurationDefinition: false));

        $this->expectException(CallCircularDependencyException::class);
        $this->expectExceptionMessageMatches(
            '/cyclical dependency.+services\.second.+services\.third.+FirstClass/'
        );

        $container->call(
            [ClassWithMethod::class, 'method'],
            ['service' => diReference('services.second')]
        );
    }

    public function testCircularWithoutAttribute(): void
    {
        $definitions = [
            diAutowire(FirstClass::class),
            diAutowire(SecondClass::class),
            diAutowire(ThirdClass::class),
        ];

        $config = new DiContainerConfig(useZeroConfigurationDefinition: false, useAttribute: false);
        $container = new DiContainer($definitions, $config);

        $this->expectException(CallCircularDependencyException::class);
        $this->expectExceptionMessageMatches(
            '/cyclical dependency.+FirstClass.+SecondClass.+ThirdClass.+FirstClass/'
        );

        $container->call(FirstClass::class);
    }

    public function testCircularInMethodWithoutAttribute(): void
    {
        $definitions = [
            diAutowire(FirstClass::class),
            diAutowire(SecondClass::class),
            diAutowire(ThirdClass::class),
            diAutowire(ClassWithMethod::class),
        ];

        $config = new DiContainerConfig(useZeroConfigurationDefinition: false, useAttribute: false);
        $container = new DiContainer($definitions, $config);

        $this->expectException(CallCircularDependencyException::class);
        $this->expectExceptionMessageMatches(
            '/cyclical dependency.+FirstClass.+SecondClass.+FirstClass/'
        );

        $container->call(
            [ClassWithMethod::class, 'method'],
            ['service' => diAutowire(ThirdClass::class)]
        );
    }
}
