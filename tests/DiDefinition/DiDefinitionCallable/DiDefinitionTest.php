<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\ServiceFour;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\ServiceOne;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\ServiceTwo;

/**
 * @covers \Kaspi\DiContainer\AttributeReader
 * @covers \Kaspi\DiContainer\Attributes\InjectByCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\Helper
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

    public function testInjectNonCallable(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot build argument via php attribute for Parameter.+Two \$two.+ServiceFour::__construct()/');

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
}
