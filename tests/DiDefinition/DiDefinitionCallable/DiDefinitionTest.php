<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\TestCase;
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
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\functionName
 * @covers \Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition
 *
 * @internal
 */
class DiDefinitionTest extends TestCase
{
    public function testGetDefinitionWhenDefinitionIsCallable(): void
    {
        $this->assertEquals('log', (new DiDefinitionCallable('log'))->getDefinition());
    }

    public function testGetDefinitionAndParseDefinitionClassMethod(): void
    {
        self::assertEquals(
            ['Tests\DiDefinition\DiDefinitionCallable\Fixtures\MainClass', 'getServiceName'],
            (new DiDefinitionCallable(MainClass::class.'::getServiceName'))->getDefinition()
        );
    }

    public function testCallableMethodArgument(): void
    {
        $def = (new DiDefinitionCallable(CallableArgument::class))
            ->bindArguments('ok')
        ;

        $this->assertEquals('ok 😀', $def->resolve(new DiContainer(config: new DiContainerConfig())));
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
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter at position #0.+ServiceFour::__construct()/');

        (new DiContainer(config: new DiContainerConfig()))->get(ServiceFour::class);
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
