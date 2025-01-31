<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\CallableArgument;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\ClassWithTaggedArg;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\MainClass;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class TaggedAsTest extends TestCase
{
    public function testTaggedAsThroughContainerWithoutAttributes(): void
    {
        $definitions = [
            diAutowire(ClassWithTaggedArg::class)
                ->bindArguments(tagged: diTaggedAs('tags.callable-handlers', false)),
            'someName1' => diCallable([MainClass::class, 'imStatic'])
                ->bindArguments('ola!')
                ->bindTag('tags.callable-handlers'),
            diAutowire(MainClass::class)
                ->bindArguments(serviceName: 'SuperServiceHere'),
            'someNameAny' => diCallable(CallableArgument::class)
                ->bindArguments('yes')
                ->bindTag('tags.callable-handlers', ['priority' => 1000]),
        ];
        $config = new DiContainerConfig(useAttribute: false);
        $container = new DiContainer($definitions, $config);

        $res = $container->get(ClassWithTaggedArg::class);

        $this->assertCount(2, $res->tagged);
        $this->assertEquals('yes 😀', $res->tagged[0]);
        $this->assertEquals('❤ola!', $res->tagged[1]);
    }

    public function testTaggedAsThroughContainerByAttributes(): void
    {
        $definitions = [
            'someName1' => diCallable([MainClass::class, 'imStatic'])
                ->bindArguments('ola!')
                ->bindTag('tags.callable-handlers'),
            diAutowire(MainClass::class)
                ->bindArguments(serviceName: 'SuperServiceHere'),
            'someNameAny' => diCallable(CallableArgument::class)
                ->bindArguments('yes')
                ->bindTag('tags.callable-handlers', ['priority' => 1000]),
        ];
        $config = new DiContainerConfig(useAttribute: true);
        $container = new DiContainer($definitions, $config);

        $res = $container->get(ClassWithTaggedArg::class);

        $this->assertEquals('yes 😀', $res->tagged->current());
        $res->tagged->next();
        $this->assertEquals('❤ola!', $res->tagged->current());
        $res->tagged->next();
        $this->assertFalse($res->tagged->valid());
    }
}
