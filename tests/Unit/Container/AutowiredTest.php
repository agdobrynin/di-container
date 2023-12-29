<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\Autowired;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\KeyGeneratorForNamedParameter;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Classes;
use Tests\Fixtures\Classes\Interfaces;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Autowired::__construct
 * @covers \Kaspi\DiContainer\Autowired::callMethod
 * @covers \Kaspi\DiContainer\Autowired::filterInputArgs
 * @covers \Kaspi\DiContainer\Autowired::getKeyGeneratorForNamedParameter
 * @covers \Kaspi\DiContainer\Autowired::resolveInstance
 * @covers \Kaspi\DiContainer\Autowired::resolveParameters
 * @covers \Kaspi\DiContainer\DiContainer::__construct
 * @covers \Kaspi\DiContainer\DiContainer::get
 * @covers \Kaspi\DiContainer\DiContainer::has
 * @covers \Kaspi\DiContainer\DiContainer::parseConstructorArguments
 * @covers \Kaspi\DiContainer\DiContainer::resolve
 * @covers \Kaspi\DiContainer\DiContainer::set
 * @covers \Kaspi\DiContainer\KeyGeneratorForNamedParameter::__construct
 * @covers \Kaspi\DiContainer\KeyGeneratorForNamedParameter::delimiter
 * @covers \Kaspi\DiContainer\KeyGeneratorForNamedParameter::id
 * @covers \Kaspi\DiContainer\KeyGeneratorForNamedParameter::idConstructor
 */
class AutowiredTest extends TestCase
{
    public function testClassNotExist(): void
    {
        $this->expectException(AutowiredExceptionInterface::class);

        (new Autowired(
            new KeyGeneratorForNamedParameter()
        ))->resolveInstance(new DiContainer(), 'ClassTest');
    }

    public function testResolveNotExistMethod(): void
    {
        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('EasyContainer::store() does not exist');

        (new Autowired(
            new KeyGeneratorForNamedParameter()
        ))->callMethod(
            new DiContainer(),
            Classes\EasyContainer::class,
            'store'
        );
    }

    public function testResolveMethodSimple(): void
    {
        $keyGen = new KeyGeneratorForNamedParameter();
        $autowire = new Autowired($keyGen);
        $container = new DiContainer(autowire: $autowire, keyGenerator: $keyGen);
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
        $keyGen = new KeyGeneratorForNamedParameter();
        $autowire = new Autowired($keyGen);
        $container = (new DiContainer(autowire: $autowire, keyGenerator: $keyGen))
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

        $container = (new DiContainer())->set($class::class);

        $this->expectException(AutowiredExceptionInterface::class);
        $this->expectExceptionMessage('Unresolvable dependency');

        (new Autowired(
            new KeyGeneratorForNamedParameter()
        ))->resolveInstance($container, $class::class);
    }

    public function testDelimiterForParam(): void
    {
        $container = new Classes\EasyContainer();
        $container->instance[Classes\Sum::class.'@@__construct@@init'] = 55;

        $sum = (new Autowired(
            new KeyGeneratorForNamedParameter('@@')
        ))
            ->resolveInstance($container, Classes\Sum::class)
        ;

        $this->assertInstanceOf(Classes\Sum::class, $sum);
        $this->assertEquals(60, $sum->add(5));
    }

    public function testObjectTypeForParameter(): void
    {
        $keyGen = new KeyGeneratorForNamedParameter('@@');
        $autowire = new Autowired($keyGen);
        $container = (new DiContainer(autowire: $autowire, keyGenerator: $keyGen))->set(
            id: Classes\ClassWithParameterTypeAsObject::class,
            arguments: ['asObject' => (object) ['name' => 'test']]
        );

        $class = $container->get(Classes\ClassWithParameterTypeAsObject::class);

        $this->assertIsObject($class->asObject);
        $this->assertEquals('test', $class->asObject->name);
    }

    public function testResolveFilteredParameters(): void
    {
        $keyGen = new KeyGeneratorForNamedParameter();
        $autowire = new Autowired($keyGen);
        $class = $autowire->resolveInstance(
            new DiContainer(autowire: $autowire, keyGenerator: $keyGen),
            Classes\Logger::class,
            ['file' => '/var/log/app.log', 'name' => 'debug-log']
        );

        $this->assertEquals('/var/log/app.log', $class->file);
        $this->assertEquals('debug-log', $class->name);
    }
}
