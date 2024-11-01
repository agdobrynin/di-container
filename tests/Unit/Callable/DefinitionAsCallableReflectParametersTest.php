<?php

declare(strict_types=1);

namespace Tests\Unit\Callable;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Unit\Callable\Fixtures\ClassMethodWithParams;
use Tests\Unit\Callable\Fixtures\SimpleInvokeClass;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 *
 * @internal
 */
class DefinitionAsCallableReflectParametersTest extends TestCase
{
    public function testDefinitionAsCallableReflectParametersClosure(): void
    {
        $definition = fn (ContainerInterface $container, SimpleInvokeClass $class) => $container->get($class->name);

        $d = new DiDefinitionCallable((new DiContainerFactory())->make(), 'x', $definition, true);
        $params = $d->getArgumentsForResolving();

        $this->assertCount(2, $params);
        $this->assertEquals(ContainerInterface::class, $params[0]->getType());
        $this->assertEquals(SimpleInvokeClass::class, $params[1]->getType());
    }

    public function testDefinitionAsCallableReflectParametersFunction(): void
    {
        $definition = 'Tests\Unit\Callable\Fixtures\testFunction';
        $d = new DiDefinitionCallable((new DiContainerFactory())->make(), 'x', $definition, true);
        $params = $d->getArgumentsForResolving();

        $this->assertCount(2, $params);
        $this->assertEquals(\ArrayIterator::class, $params[0]->getType());
        $this->assertEquals(ContainerInterface::class, $params[1]->getType());
    }

    public function testDefinitionAsCallableReflectParametersClassWithStaticMethod(): void
    {
        $definition = 'Tests\Unit\Callable\Fixtures\ClassWithStaticMethodParams::addAndCopyStatic';

        $d = new DiDefinitionCallable((new DiContainerFactory())->make(), 'x', $definition, true);
        $params = $d->getArgumentsForResolving();

        $this->assertCount(2, $params);
        $this->assertEquals(ContainerInterface::class, $params[0]->getType());
        $this->assertEquals('container', $params[0]->name);

        $this->assertEquals('string', (string) $params[1]->getType());
        $this->assertEquals('containerId', (string) $params[1]->name);
    }

    public function testDefinitionAsCallableReflectParametersInvoke(): void
    {
        $definition = new SimpleInvokeClass(name: 'Sidor');

        $d = new DiDefinitionCallable((new DiContainerFactory())->make(), 'x', $definition, true);
        $params = $d->getArgumentsForResolving();

        $this->assertCount(1, $params);
        $this->assertEquals('string', $params[0]->getType()->getName());
        $this->assertTrue($params[0]->getType()->allowsNull());
        $this->assertEquals('name', (string) $params[0]->name);
    }

    public function testDefinitionAsCallableReflectParametersMethodWithParams(): void
    {
        $definition = [new ClassMethodWithParams(), 'doSomething'];
        $d = new DiDefinitionCallable((new DiContainerFactory())->make(), 'x', $definition, true);
        $params = $d->getArgumentsForResolving();

        $this->assertCount(1, $params);
        $this->assertEquals(\ArrayIterator::class, $params[0]->getType()->getName());
    }
}
