<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\CallableArgument;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\MainClass;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;

/**
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
}
