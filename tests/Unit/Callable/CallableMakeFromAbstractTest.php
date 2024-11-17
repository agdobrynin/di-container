<?php

declare(strict_types=1);

namespace Tests\Unit\Callable;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Tests\Unit\Callable\Fixtures\SimpleInvokeClass;
use function Kaspi\DiContainer\diCallable;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionSimple
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 *
 * @internal
 */
class CallableMakeFromAbstractTest extends TestCase
{
    public function testInvokeClassAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            SimpleInvokeClass::class => ['arguments' => ['name' => 'Piter']],
        ]);
        $d = new DiDefinitionCallable(SimpleInvokeClass::class);
        $d->setContainer($container);

        $this->assertIsCallable($d->getDefinition());
        $this->assertEquals('Hello Piter!', $d->invoke($container, true));
    }

    public function testInvokeClassAsStringDefinitionDiCallableInContainer(): void
    {
        $container = (new DiContainerFactory())->make([
            SimpleInvokeClass::class => ['arguments' => ['name' => 'Piter']],
            // ... more definition ...
            'get-result' => diCallable(SimpleInvokeClass::class),
        ]);

        $this->assertEquals('Hello Piter!', $container->get('get-result'));
    }

    public function testInvokeClassAsInstance(): void
    {
        $container = (new DiContainerFactory())->make();
        $definition = [new SimpleInvokeClass(name: 'Vasiliy'), 'hello'];
        $d = new DiDefinitionCallable($definition);

        $this->assertIsCallable($d->getDefinition());
        $this->assertEquals('Vasiliy hello!', \call_user_func($d->getDefinition()));
        $this->assertEquals('Vasiliy hello!', $d->invoke($container, true));
    }

    public function testDefinitionWithNonStaticMethodAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            SimpleInvokeClass::class => ['arguments' => ['name' => 'Alex']],
        ]);

        $definition = 'Tests\Unit\Callable\Fixtures\SimpleInvokeClass::hello';
        $d = new DiDefinitionCallable($definition);
        $d->setContainer($container);

        $this->assertIsCallable($d->getDefinition());
        $this->assertEquals('Alex hello!', \call_user_func_array($d->getDefinition(), []));
        $this->assertEquals('Alex hello!', $d->invoke($container, true));
    }

    public function testDiCallableHelperAsClassAndMethod(): void
    {
        $container = (new DiContainerFactory())->make([
            SimpleInvokeClass::class => ['arguments' => ['name' => 'Alex']],
            // ... more definition
            'get-result' => diCallable(SimpleInvokeClass::class.'::hello'),
        ]);

        $this->assertEquals('Alex hello!', $container->get('get-result'));
    }

    public function testDefinitionWithNonExistMethodAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            SimpleInvokeClass::class => ['arguments' => ['name' => 'Alex']],
        ]);

        $definition = 'Tests\Unit\Callable\Fixtures\SimpleInvokeClass::methodNoyExist';
        $d = new DiDefinitionCallable($definition);
        $d->setContainer($container);

        $this->expectException(DiDefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('Definition is not callable');

        $d->getDefinition();
    }

    public function testWrongDefinitionArray(): void
    {
        $this->expectException(DiDefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('When the definition is an array');

        (new DiDefinitionCallable([]))->getDefinition();
    }

    public function testWrongDefinitionIsNotCallable(): void
    {
        $this->expectException(DiDefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('When the definition is an array');

        (new DiDefinitionCallable([self::class]))->getDefinition();
    }

    public function testWrongDefinitionIsArrayWithNulls(): void
    {
        $this->expectException(DiDefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('When the definition is an array');

        (new DiDefinitionCallable([null, null]))->getDefinition();
    }

    public function testWrongDefinitionResolveInstance(): void
    {
        $d = new DiDefinitionCallable(['NotExistClass', 'notExistMethod']);
        $d->setContainer((new DiContainerFactory())->make());

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Unresolvable dependency');

        $d->getDefinition();
    }

    public function testDefinitionWithStaticMethod(): void
    {
        $definition = 'Tests\Unit\Callable\Fixtures\ClassWithStaticMethod::foo';
        $container = (new DiContainerFactory())->make();
        $d = new DiDefinitionCallable($definition, true);

        $this->assertIsCallable($d->getDefinition());
        $this->assertEquals('I am foo static', $d->invoke($container, true));
        $this->assertEquals('I am foo static', \call_user_func_array($d->getDefinition(), []));
    }

    public function testDefinitionAsArrayWithObjects(): void
    {
        $this->expectException(DiDefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('Definition is not callable');

        (new DiDefinitionCallable([new \stdClass(), new \stdClass()]))->getDefinition();
    }

    public function testDefinitionAsFunction(): void
    {
        $container = (new DiContainerFactory())->make([
            'test' => '🎈',
            \ArrayIterator::class => ['arguments' => ['array' => ['🎃']]],
        ]);

        $definition = '\Tests\Unit\Callable\Fixtures\testFunction';
        $d = new DiDefinitionCallable($definition, false);
        $d->setContainer($container);

        $this->assertEquals('\Tests\Unit\Callable\Fixtures\testFunction', $d->getDefinition());
        $this->assertFalse($d->isSingleton());
        $this->assertEquals('x:i:0;a:2:{i:0;s:4:"🎃";i:1;s:4:"🎈";};m:a:0:{}', $d->invoke($container));
    }

    public function testDefinitionAsClassWithInvokeMethod(): void
    {
        $container = (new DiContainerFactory())->make([
            SimpleInvokeClass::class => ['arguments' => ['name' => 'Alex']],
        ]);

        $d = new DiDefinitionCallable(SimpleInvokeClass::class, arguments: ['name' => 'Ivan']);
        $d->setContainer($container);

        $this->assertIsCallable($d->getDefinition());
        $this->assertEquals('Hello Ivan!', $d->invoke($container));
    }

    public function testNonStaticMethod(): void
    {
        $container = (new DiContainerFactory())->make([
            SimpleInvokeClass::class => ['arguments' => ['name' => 'Gustav']],
        ]);
        // ....
        $d = new DiDefinitionCallable([SimpleInvokeClass::class, 'hello']);
        $d->setContainer($container);

        $this->assertIsCallable($d->getDefinition());
        $this->assertEquals('Gustav hello!', \call_user_func($d->getDefinition()));
        $this->assertEquals('Gustav hello!', $d->invoke($container, true));
    }
}
