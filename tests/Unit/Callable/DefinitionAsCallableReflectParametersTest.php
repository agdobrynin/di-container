<?php

declare(strict_types=1);

namespace Tests\Unit\Callable;

use Kaspi\DiContainer\DefinitionAsCallable;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Unit\Callable\Fixtures\ClassMethodWithParams;
use Tests\Unit\Callable\Fixtures\SimpleInvokeClass;

/**
 * @covers \Kaspi\DiContainer\DefinitionAsCallable
 *
 * @internal
 */
class DefinitionAsCallableReflectParametersTest extends TestCase
{
    public function testDefinitionAsCallableReflectParametersClosure(): void
    {
        $definition = fn (ContainerInterface $container, SimpleInvokeClass $class) => $container->get($class->name);

        $params = DefinitionAsCallable::reflectParameters($definition);

        $this->assertCount(2, $params);
        $this->assertEquals(ContainerInterface::class, $params[0]->getType());
        $this->assertEquals(SimpleInvokeClass::class, $params[1]->getType());
    }

    public function testDefinitionAsCallableReflectParametersFunction(): void
    {
        $params = DefinitionAsCallable::reflectParameters('Tests\Unit\Callable\Fixtures\testFunction');

        $this->assertCount(2, $params);
        $this->assertEquals(\ArrayIterator::class, $params[0]->getType());
        $this->assertEquals(ContainerInterface::class, $params[1]->getType());
    }

    public function testDefinitionAsCallableReflectParametersClassWithStaticMethod(): void
    {
        $params = DefinitionAsCallable::reflectParameters('Tests\Unit\Callable\Fixtures\ClassWithStaticMethodParams::addAndCopyStatic');

        $this->assertCount(2, $params);
        $this->assertEquals(ContainerInterface::class, $params[0]->getType());
        $this->assertEquals('container', $params[0]->name);

        $this->assertEquals('string', (string) $params[1]->getType());
        $this->assertEquals('containerId', (string) $params[1]->name);
    }

    public function testDefinitionAsCallableReflectParametersInvoke(): void
    {
        $params = DefinitionAsCallable::reflectParameters(new SimpleInvokeClass(name: 'Sidor'));

        $this->assertCount(1, $params);
        $this->assertEquals('string', $params[0]->getType()->getName());
        $this->assertTrue($params[0]->getType()->allowsNull());
        $this->assertEquals('name', (string) $params[0]->name);
    }

    public function testDefinitionAsCallableReflectParametersMethodWithParams(): void
    {
        $params = DefinitionAsCallable::reflectParameters([new ClassMethodWithParams(), 'doSomething']);

        $this->assertCount(1, $params);
        $this->assertEquals(\ArrayIterator::class, $params[0]->getType()->getName());
    }
}
