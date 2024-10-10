<?php

declare(strict_types=1);

namespace Tests\Unit\Container\ContainerMethodCall;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Unit\Container\ContainerMethodCall\Fixtures\ClassInjectedServiceInConstructor;
use Tests\Unit\Container\ContainerMethodCall\Fixtures\ClassInvokeAndInjectedServiceInConstructor;
use Tests\Unit\Container\ContainerMethodCall\Fixtures\ClassWithMethodWithDependency;
use Tests\Unit\Container\ContainerMethodCall\Fixtures\ClassWithStaticMethod;
use Tests\Unit\Container\ContainerMethodCall\Fixtures\GreetingService;
use Tests\Unit\Container\ContainerMethodCall\Fixtures\NameService;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\DefinitionAsCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerDefinition
 * @covers \Kaspi\DiContainer\DiContainerFactory
 *
 * @internal
 */
class ContainerMethodCallTest extends TestCase
{
    public function testBuildInFunction(): void
    {
        $res = (new DiContainerFactory())->make()->call('\log', ['num' => 10]);
        $this->assertEquals(2.302585092994046, $res);
    }

    public function testBuildInFunctionWithException(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Unresolvable dependency');

        (new DiContainerFactory())->make()->call('\log');
    }

    public function testUserFunction(): void
    {
        $res = (new DiContainerFactory())->make([
            'hello' => 'hello world!',
        ])
            ->call('\Tests\Unit\Container\ContainerMethodCall\Fixtures\testFunction', ['containerId' => 'hello'])
        ;

        $this->assertEquals('hello world!', $res);
    }

    public function testUserFunctionWithoutParameterType(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Unresolvable dependency');

        (new DiContainerFactory())->make()
            ->call('\Tests\Unit\Container\ContainerMethodCall\Fixtures\testFunctionNotTypedParameter', ['var' => 'hello'])
        ;
    }

    public function testClassWithStaticMethod(): void
    {
        $res = (new DiContainerFactory())->make()
            ->call(ClassWithStaticMethod::class.'::hello', ['greeting' => 'Welcome', 'name' => 'Ivan'])
        ;

        $this->assertEquals('Welcome Ivan🎈', $res);
    }

    public function testClassWithInvokeMethod(): void
    {
        $res = (new DiContainerFactory())->make([
            NameService::class => [
                DiContainerInterface::ARGUMENTS => [
                    'name' => 'Noa',
                ],
            ],
        ])
            ->call(ClassInvokeAndInjectedServiceInConstructor::class, ['greeting' => 'Aloha'])
        ;

        $this->assertEquals('Aloha Noa 🕶', $res);
    }

    public function testClassWithMethodAsString(): void
    {
        $container = (new DiContainerFactory())->make([
            NameService::class => [
                DiContainerInterface::ARGUMENTS => [
                    'name' => 'Jimmy',
                ],
            ],
        ]);

        $definition = 'Tests\Unit\Container\ContainerMethodCall\Fixtures\ClassInjectedServiceInConstructor::sayHello';
        $res = $container->call($definition, ['greeting' => 'Hi']);

        $this->assertEquals('Hi Jimmy', $res);
    }

    public function testClassWithMethodAsArray(): void
    {
        $container = (new DiContainerFactory())->make([
            NameService::class => [
                DiContainerInterface::ARGUMENTS => [
                    'name' => 'Jimmy',
                ],
            ],
        ]);

        $definition = [ClassInjectedServiceInConstructor::class, 'sayHello'];
        $res = $container->call($definition, ['greeting' => 'Hi']);

        $this->assertEquals('Hi Jimmy', $res);
    }

    public function testResolveParamsInFunction(): void
    {
        $container = (new DiContainerFactory())->make();
        $action = static function (\ArrayIterator $iterator): \ArrayIterator {
            $iterator->append('Hello');

            return $iterator;
        };

        $res = $container->call($action);

        $this->assertInstanceOf(\Iterator::class, $res);
        $this->assertCount(1, $res);
        $this->assertEquals('x:i:0;a:1:{i:0;s:5:"Hello";};m:a:0:{}', $res->serialize());
    }

    public function testCallMethodWithDependency(): void
    {
        $container = (new DiContainerFactory())->make([
            NameService::class => [DiContainerInterface::ARGUMENTS => ['name' => 'Jimmy']],
            GreetingService::class => [DiContainerInterface::ARGUMENTS => ['greeting' => 'Hello']],
        ]);

        $res = $container->call([ClassWithMethodWithDependency::class, 'sayHello'], ['icon' => '🎉']);

        $this->assertEquals('Hello Jimmy 🎉', $res);
    }

    public function testCallMethodWithDependencyWithInject(): void
    {
        $container = (new DiContainerFactory())->make();
        $res = $container->call([ClassWithMethodWithDependency::class, 'sayHello'], ['icon' => '👓']);

        $this->assertEquals('Aloha Piter 👓', $res);
    }
}
