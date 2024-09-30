<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\Autowired;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Classes;
use Tests\Fixtures\Classes\Interfaces;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject::makeFromReflection
 * @covers \Kaspi\DiContainer\Autowired
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 */
class AutowiredTest extends TestCase
{
    public function testClassNotExist(): void
    {
        $this->expectExceptionMessage('ClassTest');
        $autowired = new Autowired();

        $autowired->resolveInstance(new DiContainer(config: new DiContainerConfig($autowired)), 'ClassTest');
    }

    public function testResolveNotExistMethod(): void
    {
        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('EasyContainer::store() does not exist');

        (new Autowired())->callMethod(
            new DiContainer(),
            Classes\EasyContainer::class,
            'store'
        );
    }

    public function testResolveMethodSimple(): void
    {
        $autowire = new Autowired();
        $container = new DiContainer(config: new DiContainerConfig(new Autowired()));
        $result = $autowire->callMethod(
            container: $container,
            id: Classes\EasyContainer::class,
            method: 'has',
            methodArgs: ['id' => 'DependencyId']
        );

        $this->assertFalse($result);
    }

    public function testResolveMethodWithDependencies(): void
    {
        $autowire = new Autowired();
        $config = new DiContainerConfig($autowire);
        $container = (new DiContainer(config: $config))
            ->set(
                Interfaces\SumInterface::class,
                Classes\Sum::class
            )
            ->set(id: Classes\Sum::class, arguments: ['init' => 90])
        ;

        $result = $autowire->callMethod(
            container: $container,
            id: Classes\MethodWithDependencies::class,
            method: 'view',
            methodArgs: ['value' => 10]
        );

        $this->assertEquals(100, $result);
    }

    public function testUnresolvedBuildInParam(): void
    {
        $class = new class(1) {
            public string $w = 'abc';

            public function __construct(int $val) {}
        };

        $autowire = new Autowired();
        $config = new DiContainerConfig($autowire);
        $container = (new DiContainer(config: $config))->set($class::class);

        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('Unresolvable dependency');

        (new Autowired())->resolveInstance($container, $class::class);
    }

    public function testObjectTypeForParameter(): void
    {
        $container = (new DiContainer(config: new DiContainerConfig(new Autowired())))->set(
            id: Classes\ClassWithParameterTypeAsObject::class,
            arguments: ['asObject' => (object) ['name' => 'test']]
        );

        $class = $container->get(Classes\ClassWithParameterTypeAsObject::class);

        $this->assertIsObject($class->asObject);
        $this->assertEquals('test', $class->asObject->name);
    }

    public function testResolveFilteredParameters(): void
    {
        $autowire = new Autowired();
        $class = $autowire->resolveInstance(
            new DiContainer(config: new DiContainerConfig($autowire)),
            Classes\Logger::class,
            ['file' => '/var/log/app.log', 'name' => 'debug-log']
        );

        $this->assertEquals('/var/log/app.log', $class->file);
        $this->assertEquals('debug-log', $class->name);
    }

    public function testIsInstantiable(): void
    {
        $autowire = new Autowired();

        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('class is not instantiable');

        $autowire->resolveInstance(new DiContainer(), Classes\AbstractClass::class);
    }

    public function testCallStaticMethod()
    {
        $a = new Autowired();
        $r = $a->callMethod(new DiContainer(), Classes\ClassWithStaticMethod::class, 'talk');

        $this->assertEquals('Hello!', $r);
    }
}
