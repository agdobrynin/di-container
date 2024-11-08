<?php

declare(strict_types=1);

namespace Tests\Unit\Callable;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\CallableParserTrait;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\Unit\Callable\Fixtures\SimpleInvokeClass;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionSimple
 *
 * @internal
 */
class CallableMakeFromAbstractTest extends TestCase
{
    protected $callableParser;

    public function setUp(): void
    {
        $this->callableParser = new class() {
            use CallableParserTrait;

            public static function make($definition, $container)
            {
                return self::parseCallable($definition, $container);
            }
        };
    }

    public function tearDown(): void
    {
        $this->callableParser = null;
    }

    public function testInvokeClassAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            SimpleInvokeClass::class => ['arguments' => ['name' => 'Piter']],
        ]);

        $callable = $this->callableParser::make(SimpleInvokeClass::class, $container);
        $d = new DiDefinitionCallable('x', $callable, true);

        $this->assertIsCallable($d->getDefinition());
        $this->assertEquals('Hello Piter!', $d->invoke($container, true));
    }

    public function testInvokeClassAsInstance(): void
    {
        $definition = [new SimpleInvokeClass(name: 'Vasiliy'), 'hello'];
        $container = (new DiContainerFactory())->make();
        $callable = $this->callableParser::make($definition, $container);
        $d = new DiDefinitionCallable('x', $callable, true);

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
        $callable = $this->callableParser::make($definition, $container);

        $d = new DiDefinitionCallable('x', $callable, true);

        $this->assertIsCallable($d->getDefinition());
        $this->assertEquals('Alex hello!', \call_user_func_array($d->getDefinition(), []));
        $this->assertEquals('Alex hello!', $d->invoke($container, true));
    }

    public function testDefinitionWithNonExistMethodAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            SimpleInvokeClass::class => ['arguments' => ['name' => 'Alex']],
        ]);

        $this->expectException(DiDefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('Definition is not callable');

        $definition = 'Tests\Unit\Callable\Fixtures\SimpleInvokeClass::methodNoyExist';
        $this->callableParser::make($definition, $container);
    }

    public function testWrongDefinitionArray(): void
    {
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

        $this->expectException(DiDefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('When the definition is an array');

        $this->callableParser::make([], $container);
    }

    public function testWrongDefinitionIsNotCallable(): void
    {
        $this->expectException(DiDefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('When the definition is an array');

        $this->callableParser::make([self::class], (new DiContainerFactory())->make());
    }

    public function testWrongDefinitionIsArrayWithNulls(): void
    {
        $this->expectException(DiDefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('When the definition is an array');

        $this->callableParser::make([null, null], (new DiContainerFactory())->make());
    }

    public function testWrongDefinitionResolveInstance(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Unresolvable dependency');

        $this->callableParser::make(['NotExistClass', 'notExistMethod'], (new DiContainerFactory())->make());
    }

    public function testDefinitionWithStaticMethod(): void
    {
        $definition = 'Tests\Unit\Callable\Fixtures\ClassWithStaticMethod::foo';
        $container = (new DiContainerFactory())->make();
        $callable = $this->callableParser::make($definition, $container);

        $d = new DiDefinitionCallable('x', $callable, true);

        $this->assertIsCallable($d->getDefinition());
        $this->assertEquals('I am foo static', $d->invoke($container, true));
        $this->assertEquals('I am foo static', \call_user_func_array($d->getDefinition(), []));
    }

    public function testDefinitionAsArrayWithObjects(): void
    {
        $this->expectException(DiDefinitionCallableExceptionInterface::class);
        $this->expectExceptionMessage('Definition is not callable');

        $this->callableParser::make([new \stdClass(), new \stdClass()], (new DiContainerFactory())->make());
    }

    public function testDefinitionAsFunction(): void
    {
        $container = (new DiContainerFactory())->make([
            'test' => '🎈',
            \ArrayIterator::class => ['arguments' => ['array' => ['🎃']]],
        ]);

        $definition = '\Tests\Unit\Callable\Fixtures\testFunction';
        $callable = $this->callableParser::make($definition, $container);

        $d = new DiDefinitionCallable('x', $callable, false);

        $this->assertEquals('\Tests\Unit\Callable\Fixtures\testFunction', $d->getDefinition());
        $this->assertFalse($d->isSingleton());
        $this->assertEquals('x', $d->getContainerId());
        $this->assertEquals('x:i:0;a:2:{i:0;s:4:"🎃";i:1;s:4:"🎈";};m:a:0:{}', $d->invoke($container, true));
    }
}
