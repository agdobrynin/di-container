<?php

declare(strict_types=1);

namespace Tests\Unit\Callable;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionCallableExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\Unit\Callable\Fixtures\SimpleInvokeClass;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
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

        $d = new DiDefinitionCallable($container, 'x', $definition, true);

        $this->assertIsCallable($d->getDefinition());
        $this->assertEquals('Hello Piter!', $d->invoke([]));
        $this->assertEquals('Hello Ivan!', $d->invoke(['name' => 'Ivan']));
    }

    public function testInvokeClassAsInstance(): void
    {
        $definition = [new SimpleInvokeClass(name: 'Vasiliy'), 'hello'];
        $container = (new DiContainerFactory())->make([]);
        $d = new DiDefinitionCallable($container, 'x', $definition, true);

        $this->assertIsCallable($d->getDefinition());
        $this->assertEquals('Vasiliy hello!', \call_user_func($d->getDefinition()));
        $this->assertEquals('Vasiliy hello!', $d->invoke([]));
    }

    public function testDefinitionWithNonStaticMethodAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            SimpleInvokeClass::class => ['arguments' => ['name' => 'Alex']],
        ]);

        $definition = 'Tests\Unit\Callable\Fixtures\SimpleInvokeClass::hello';

        $d = new DiDefinitionCallable($container, 'x', $definition, true);

        $this->assertIsCallable($d->getDefinition());
        $this->assertEquals('Alex hello!', \call_user_func_array($d->getDefinition(), []));
        $this->assertEquals('Alex hello!', $d->invoke([]));
    }

    public function testDefinitionWithNonExistMethodAsString(): void
    {
        $this->expectException(DefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('Definition is not callable');

        $container = (new DiContainerFactory())->make([
            SimpleInvokeClass::class => ['arguments' => ['name' => 'Alex']],
        ]);

        $definition = 'Tests\Unit\Callable\Fixtures\SimpleInvokeClass::methodNoyExist';
        new DiDefinitionCallable($container, 'x', $definition, true);
    }

    public function testWrongDefinitionArray(): void
    {
        $this->expectException(DefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('When the definition is an array');
        $container = new class() implements ContainerInterface {
            public function get(string $id)
            {
                return $id;
            }

            public function has(string $id): bool
            {
                return true;
            }
        };
        new DiDefinitionCallable($container, 'x', [], true);
    }

    public function testWrongDefinitionIsNotCallable(): void
    {
        $this->expectException(DefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('When the definition is an array');

        new DiDefinitionCallable((new DiContainerFactory())->make(), 'x', [self::class], true);
    }

    public function testWrongDefinitionIsArrayWithNulls(): void
    {
        $this->expectException(DefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('When the definition is an array');

        new DiDefinitionCallable((new DiContainerFactory())->make(), 'x', [null, null], true);
    }

    public function testWrongDefinitionResolveInstance(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Unresolvable dependency');

        $definition = ['NotExistClass', 'notExistMethod'];

        new DiDefinitionCallable((new DiContainerFactory())->make(), 'x', $definition, true);
    }

    public function testDefinitionWithStaticMethod(): void
    {
        $definition = 'Tests\Unit\Callable\Fixtures\ClassWithStaticMethod::foo';

        $d = new DiDefinitionCallable((new DiContainerFactory())->make(), 'x', $definition, true);

        $this->assertIsCallable($d->getDefinition());
        $this->assertEquals('I am foo static', $d->invoke([]));
        $this->assertEquals('I am foo static', \call_user_func_array($d->getDefinition(), []));
    }

    public function testDefinitionAsArrayWithObjects(): void
    {
        $this->expectException(DefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('Definition is not callable');

        $definition = [new \stdClass(), new \stdClass()];
        new DiDefinitionCallable((new DiContainerFactory())->make(), 'x', $definition, true);
    }
}
