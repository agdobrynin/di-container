<?php

declare(strict_types=1);

namespace Tests\Unit\Definition;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Unit\Definition\Fixtures\CallableStaticMethod;
use Tests\Unit\Definition\Fixtures\Generated\Service0;
use Tests\Unit\Definition\Fixtures\Generated\ServiceImplementation;
use Tests\Unit\Definition\Fixtures\Generated\ServiceInterface;
use Tests\Unit\Definition\Fixtures\SimpleService;
use Tests\Unit\Definition\Fixtures\SimpleServiceWithArgument;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diValue;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diValue
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 *
 * @internal
 */
class DefinitionHelperFunctionTest extends TestCase
{
    public function testDiValueFunction(): void
    {
        $container = (new DiContainerFactory())->make([
            'log' => diValue(['a' => 'aaa']),
        ]);

        $this->assertEquals(['a' => 'aaa'], $container->get('log'));
    }

    public function testDiValueFunctionWithoutContainerIdentifier(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        (new DiContainerFactory())->make([
            diValue(['a' => 'aaa']),
        ]);
    }

    public function testDiAutowireFunctionWithEmptyIdentifier(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        (new DiContainerFactory())->make([
            diAutowire('   '),
        ]);
    }

    public function testDiAutowireFunction(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(SimpleService::class),
        ]);

        $class = $container->get(SimpleService::class);

        $this->assertInstanceOf(SimpleService::class, $class);
    }

    public function testDiAutowireFunctionNonExistClass(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire('non-exist-class'),
        ]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('does not exist');

        $container->get('non-exist-class');
    }

    public function testDiDefinitionFunctionCallable(): void
    {
        $container = (new DiContainerFactory())->make([
            'factoryOne' => diCallable([CallableStaticMethod::class, 'myMethod'], arguments: ['name' => 'Ivan', 'city' => 'Vice city']),
        ]);

        $this->assertEquals('Hello Ivan! Welcome to Vice city 🎈', $container->get('factoryOne'));
    }

    public function testDiDefinitionFunctionCallableBySetContainer(): void
    {
        $container = (new DiContainerFactory())->make()
            ->set(
                'factoryTwo',
                diCallable([CallableStaticMethod::class, 'myMethod'])
                    ->addArgument('name', 'Vasiliy')
                    ->addArgument('city', 'Narnia')
            )
        ;

        $this->assertEquals('Hello Vasiliy! Welcome to Narnia 🎈', $container->get('factoryTwo'));
    }

    public function testDiDefinitionFunctionCallableWithoutIdentifier(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('must be a non-empty string');

        (new DiContainerFactory())->make([
            diCallable([CallableStaticMethod::class, 'myMethod']),
        ]);
    }

    public function testCallableWrongArray(): void
    {
        $container = (new DiContainerFactory())->make([
            'service' => diCallable([self::class]),
        ]);

        $this->expectException(ContainerExceptionInterface::class);

        $container->get('service');
    }

    public function testCallableWrongArrayBySetContainer(): void
    {
        $container = (new DiContainerFactory())->make()
            ->set('service', diCallable([self::class]))
        ;

        $this->expectException(ContainerExceptionInterface::class);

        $container->get('service');
    }

    public function testCallableWithInstancedClassWithMethod(): void
    {
        $container = (new DiContainerFactory())->make([
            'refresh-token' => diCallable([new SimpleServiceWithArgument('aaa-bbb-ccc'), 'getRefreshToken']),
        ]);

        $this->assertEquals(['token' => 'aaa-bbb-ccc', 'refreshToken' => 'qqq-ccc-fff'], $container->get('refresh-token'));
    }

    public function testDefinitionAsAutowireHelperWithAddArgument(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(SimpleServiceWithArgument::class)
                ->addArgument('token', 'aaa-bbb-ccc'),
        ]);

        $this->assertEquals('aaa-bbb-ccc', $container->get(SimpleServiceWithArgument::class)->getToken());
    }

    public function testCallableWithInstancedClassWithMethodBySetContainer(): void
    {
        $container = (new DiContainerFactory())->make()
            ->set('refresh-token', diCallable([new SimpleServiceWithArgument('aaa-bbb-ccc'), 'getRefreshToken']))
        ;

        $this->assertEquals(['token' => 'aaa-bbb-ccc', 'refreshToken' => 'qqq-ccc-fff'], $container->get('refresh-token'));
    }

    public function testDefinitionAsAutowireWithSetContainer(): void
    {
        $container = (new DiContainerFactory())->make()
            ->set(ServiceInterface::class, diAutowire(ServiceImplementation::class))
        ;

        $class = $container->get(ServiceInterface::class);

        $this->assertInstanceOf(ServiceImplementation::class, $class);
        $this->assertInstanceOf(Service0::class, $class->service);
    }
}
