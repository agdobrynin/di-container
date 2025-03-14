<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\CallableArgument;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\MainClass;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\MakeServiceTwo;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\ServiceFour;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\ServiceOne;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\ServiceThree;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\ServiceTwo;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;

/**
 * @covers \Kaspi\DiContainer\Attributes\InjectByCallable
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 *
 * @internal
 */
class DiDefinitionTest extends TestCase
{
    public function testGetDefinitionWhenDefinitionIsCallable(): void
    {
        $this->assertEquals('log', (new DiDefinitionCallable('log'))->getDefinition());
    }

    public function testGetDefinitionAndParseDefinitionWithoutContainerFail(): void
    {
        $this->expectException(ContainerNeedSetExceptionInterface::class);

        (new DiDefinitionCallable(MainClass::class.'::getServiceName'))->getDefinition();
    }

    public function testGetDefinitionAndParseDefinitionSuccess(): void
    {
        $container = new DiContainer([
            diAutowire(MainClass::class)
                ->bindArguments(serviceName: 'someServiceName'),
        ]);

        $definition = (new DiDefinitionCallable([MainClass::class, 'getServiceName']))
            ->setContainer($container)
            ->getDefinition()
        ;

        $this->assertIsCallable($definition);
        $this->assertInstanceOf(MainClass::class, $definition[0]);
        $this->assertEquals('getServiceName', $definition[1]);
    }

    public function testCallableMethodArgument(): void
    {
        $def = (new DiDefinitionCallable(CallableArgument::class))
            ->bindArguments('ok')
        ;
        $def->setContainer(new DiContainer(config: new DiContainerConfig()));

        $this->assertEquals('ok 😀', $def->invoke());
    }

    public function testCallableByContainer(): void
    {
        $container = new DiContainer([
            'say.hu' => diCallable(CallableArgument::class)
                ->bindArguments('ok'),
        ], new DiContainerConfig());

        $this->assertEquals('ok 😀', $container->get('say.hu'));
    }

    public function testInjectNonCallable(): void
    {
        $container = new DiContainer(config: new DiContainerConfig());

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Definition is not callable. Got: type "string", value: \'noneCallableString\'.');

        $container->get(ServiceFour::class);
    }

    public function testInjectCallableFromStaticMethod(): void
    {
        $container = new DiContainer(config: new DiContainerConfig());

        $srv = $container->get(ServiceOne::class);

        $this->assertEquals('fromStatic', $srv->two->param);
    }

    public function testInjectCallableFromFunction(): void
    {
        $container = new DiContainer(config: new DiContainerConfig());

        $srv = $container->get(ServiceTwo::class);

        $this->assertEquals('fromFunction', $srv->two->param);
    }

    public function testInjectCallableFromClassWithInvokeMethod(): void
    {
        $container = new DiContainer([
            diAutowire(MakeServiceTwo::class)
                ->bindArguments(def: 'fromInvokeMethod'),
        ], new DiContainerConfig());

        $srv = $container->get(ServiceThree::class);

        $this->assertEquals('fromInvokeMethod', $srv->two->param);
    }
}
