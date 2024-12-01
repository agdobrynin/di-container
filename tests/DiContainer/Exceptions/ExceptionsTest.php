<?php

declare(strict_types=1);

namespace Tests\DiContainer\Exceptions;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\DiContainer\Exceptions\Fixtures\DependencyClass;
use Tests\DiContainer\Exceptions\Fixtures\FirstClass;
use Tests\DiContainer\Exceptions\Fixtures\SecondClass;
use Tests\DiContainer\Exceptions\Fixtures\SuperClass;
use Tests\DiContainer\Exceptions\Fixtures\ThirdClass;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class ExceptionsTest extends TestCase
{
    public function testAutowireDefinitionIsNotClass(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire('MyNonExistClass'),
        ]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Class "MyNonExistClass" does not exist');

        $container->get('MyNonExistClass');
    }

    public function testCircularDependencyWithoutAttribute(): void
    {
        $config = new DiContainerConfig(
            useAttribute: false
        );

        $container = new DiContainer(config: $config);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches(
            '/cyclical dependency.+FirstClass.+SecondClass.+ThirdClass.+FirstClass/'
        );

        $container->get(FirstClass::class);
    }

    public function testCircularDependencyViaAttribute(): void
    {
        $config = new DiContainerConfig(
            useAttribute: true
        );

        $def = [
            'services.second' => diAutowire(SecondClass::class),
            'services.third' => diAutowire(ThirdClass::class),
        ];
        $container = new DiContainer($def, config: $config);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches(
            '/cyclical dependency.+FirstClass.+services\.second.+services\.third.+FirstClass/'
        );

        $container->get(FirstClass::class);
    }

    public function testDependencyCannotResolveNotFound(): void
    {
        $container = (new DiContainerFactory())->make();

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Unresolvable dependency.+string \$value.+DependencyClass/');

        $container->get(SuperClass::class);
    }

    public function testManyArguments(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(DependencyClass::class)
                ->bindArguments(value: 'Ok', value2: 'Ok'),
        ]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Too many input arguments');

        $container->get(DependencyClass::class);
    }
}
