<?php

declare(strict_types=1);

namespace Tests\Unit\Callable;

use Kaspi\DiContainer\DefinitionAsCallable;
use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionCallableExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Tests\Unit\Callable\Fixtures\SimpleInvokeClass;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\DefinitionAsCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerDefinition
 * @covers \Kaspi\DiContainer\DiContainerFactory
 *
 * @internal
 */
class DefinitionAsCallableMakeFromAbstractTest extends TestCase
{
    public function testInvokeClassAsString(): void
    {
        $definition = SimpleInvokeClass::class;
        $container = (new DiContainerFactory())->make([
            SimpleInvokeClass::class => ['arguments' => ['name' => 'Piter']],
        ]);

        $callable = DefinitionAsCallable::makeFromAbstract($definition, $container);

        $this->assertIsCallable($callable);
        $this->assertEquals('Hello Piter!', $callable());
    }

    public function testInvokeClassAsInstance(): void
    {
        $definition = [new SimpleInvokeClass(name: 'Vasiliy'), 'hello'];
        $callable = DefinitionAsCallable::makeFromAbstract($definition, (new DiContainerFactory())->make());

        $this->assertIsCallable($callable);
        $this->assertEquals('Vasiliy hello!', \call_user_func($callable));
    }

    public function testDefinitionWithNonStaticMethodAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            SimpleInvokeClass::class => ['arguments' => ['name' => 'Alex']],
        ]);

        $definition = 'Tests\Unit\Callable\Fixtures\SimpleInvokeClass::hello';

        $callable = DefinitionAsCallable::makeFromAbstract($definition, $container);

        $this->assertIsCallable($callable);
        $this->assertEquals('Alex hello!', \call_user_func_array($callable, []));
    }

    public function testDefinitionWithNonExistMethodAsString(): void
    {
        $this->expectException(DefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('Definition is not callable');

        $container = (new DiContainerFactory())->make([
            SimpleInvokeClass::class => ['arguments' => ['name' => 'Alex']],
        ]);

        $definition = 'Tests\Unit\Callable\Fixtures\SimpleInvokeClass::methodNoyExist';

        DefinitionAsCallable::makeFromAbstract($definition, $container);
    }

    public function testWrongDefinitionArray(): void
    {
        $this->expectException(DefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('Wrong parameter for parse definition');

        DefinitionAsCallable::makeFromAbstract(
            [],
            (new DiContainerFactory())->make()
        );
    }

    public function testWrongDefinitionIsNotCallable(): void
    {
        $this->expectException(DefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('Wrong parameter for parse definition');

        DefinitionAsCallable::makeFromAbstract(
            [],
            (new DiContainerFactory())->make()
        );
    }

    public function testWrongDefinitionResolveInstance(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Unresolvable dependency');

        $definition = ['NotExistClass', 'notExistMethod'];

        DefinitionAsCallable::makeFromAbstract($definition, (new DiContainerFactory())->make());
    }

    public function testDefinitionWithStaticMethod(): void
    {
        $definition = 'Tests\Unit\Callable\Fixtures\ClassWithStaticMethod::foo';

        $callable = DefinitionAsCallable::makeFromAbstract($definition, (new DiContainerFactory())->make());

        $this->assertIsCallable($callable);
        $this->assertEquals('I am foo static', \call_user_func_array($callable, []));
    }
}
