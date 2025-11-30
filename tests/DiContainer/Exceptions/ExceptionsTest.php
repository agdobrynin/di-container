<?php

declare(strict_types=1);

namespace Tests\DiContainer\Exceptions;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\DiContainer\Exceptions\Fixtures\DependencyClass;
use Tests\DiContainer\Exceptions\Fixtures\FirstClass;
use Tests\DiContainer\Exceptions\Fixtures\SecondClass;
use Tests\DiContainer\Exceptions\Fixtures\SuperClass;
use Tests\DiContainer\Exceptions\Fixtures\ThirdClass;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\Helper
 * @covers \Kaspi\DiContainer\Traits\ContextExceptionTrait
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
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter at position #0.+FirstClass::__construct()/');

        $config = new DiContainerConfig(
            useAttribute: false
        );

        (new DiContainer(config: $config))->get(FirstClass::class);
    }

    public function testCircularDependencyViaAttribute(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter at position #0.+FirstClass::__construct()/');

        $config = new DiContainerConfig(
            useAttribute: true
        );

        $def = [
            'services.second' => diAutowire(SecondClass::class),
            'services.third' => diAutowire(ThirdClass::class),
        ];
        $container = new DiContainer($def, config: $config);

        $container->get(FirstClass::class);
    }

    public function testDependencyCannotResolveNotFound(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter at position #0.+SuperClass::__construct()/');

        (new DiContainerFactory())->make()->get(SuperClass::class);
    }

    public function testManyArguments(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(DependencyClass::class)
                ->bindArguments(value: 'Ok', value2: 'Ok'),
        ]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot build arguments for .+DependencyClass::__construct\(\)\. Does not accept unknown named parameter \$value2\./');

        $container->get(DependencyClass::class);
    }

    public function testInvalidArgumentName(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(DependencyClass::class)
                ->bindArguments(noneExistParam: 'Ok'),
        ]);

        try {
            $container->get(DependencyClass::class);
        } catch (ContainerExceptionInterface $exception) {
            self::assertInstanceOf(ArgumentBuilderExceptionInterface::class, $exception);
            self::assertMatchesRegularExpression('/Cannot build argument via type hint for Parameter #0 \[ <required> string \$value ] in .+DependencyClass::__construct\(\)\./', $exception->getMessage());

            self::assertInstanceOf(AutowireExceptionInterface::class, $exception->getPrevious());
            self::assertMatchesRegularExpression('/Cannot automatically resolve dependency.+Please specify the Parameter #0 \[ <required> string \$value ]/', $exception->getPrevious()->getMessage());
        }
    }
}
