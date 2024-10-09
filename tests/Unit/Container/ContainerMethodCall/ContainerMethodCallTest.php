<?php

declare(strict_types=1);

namespace Tests\Unit\Container\ContainerMethodCall;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Unit\Container\ContainerMethodCall\Fixtures\ClassInjectedServiceInConstructor;
use Tests\Unit\Container\ContainerMethodCall\Fixtures\ClassInvokeAndInjectedServiceInConstructor;
use Tests\Unit\Container\ContainerMethodCall\Fixtures\ClassWithStaticMethod;
use Tests\Unit\Container\ContainerMethodCall\Fixtures\SimpleService;

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
            SimpleService::class => [
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
            SimpleService::class => [
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
            SimpleService::class => [
                DiContainerInterface::ARGUMENTS => [
                    'name' => 'Jimmy',
                ],
            ],
        ]);

        $definition = [ClassInjectedServiceInConstructor::class, 'sayHello'];
        $res = $container->call($definition, ['greeting' => 'Hi']);

        $this->assertEquals('Hi Jimmy', $res);
    }
}
