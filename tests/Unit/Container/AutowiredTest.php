<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\Autowired;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;
use Kaspi\DiContainer\KeyGeneratorForNamedParameter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Classes\EasyContainer;

/**
 * @internal
 */
#[CoversClass(DiContainer::class)]
#[CoversClass(Autowired::class)]
#[CoversClass(KeyGeneratorForNamedParameter::class)]
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
        ))->callMethod(new DiContainer(), \Tests\Fixtures\Classes\EasyContainer::class, 'store');
    }

    public function testResolveMethodSimple(): void
    {
        $autowire = new Autowired(new KeyGeneratorForNamedParameter());
        $container = new DiContainer(autowire: $autowire);
        $result = $autowire->callMethod(
            container: $container,
            id: \Tests\Fixtures\Classes\EasyContainer::class,
            method: 'has',
            methodArgs: ['id' => 'DependencyId']
        );

        $this->assertFalse($result);
    }

    public function testResolveMethodWithDependencies(): void
    {
        $autowire = new Autowired(new KeyGeneratorForNamedParameter());
        $container = (new DiContainer(autowire: $autowire))
            ->set(
                \Tests\Fixtures\Classes\Interfaces\SumInterface::class,
                \Tests\Fixtures\Classes\Sum::class
            )
            ->set(\Tests\Fixtures\Classes\Sum::class, ['init' => 90])
        ;

        $result = $autowire->callMethod(
            container: $container,
            id: \Tests\Fixtures\Classes\MethodWithDependencies::class,
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
        $container = new EasyContainer();
        $container->instance[\Tests\Fixtures\Classes\Sum::class.'@@__construct@@init'] = 55;

        $sum = (new Autowired(
            new KeyGeneratorForNamedParameter('@@')
        ))
            ->resolveInstance($container, \Tests\Fixtures\Classes\Sum::class)
        ;

        $this->assertInstanceOf(\Tests\Fixtures\Classes\Sum::class, $sum);
        $this->assertEquals(60, $sum->add(5));
    }

    public function testUnknownTypeForParameter(): void
    {
        $autowire = new Autowired(
            new KeyGeneratorForNamedParameter('@@')
        );
        $container = (new DiContainer(autowire: $autowire))->set(
            \Tests\Fixtures\Classes\ClassWithParameterTypeAsObject::class,
            ['asObject' => (object) ['name' => 'test']]
        );

        $class = $container->get(\Tests\Fixtures\Classes\ClassWithParameterTypeAsObject::class);

        $this->assertIsObject($class->asObject);
        $this->assertEquals('test', $class->asObject->name);
    }
}
