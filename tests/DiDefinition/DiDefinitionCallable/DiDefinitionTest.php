<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Reflection\ReflectionMethodByDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\ServiceFour;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\ServiceOne;
use Tests\DiDefinition\DiDefinitionCallable\Fixtures\ServiceTwo;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(InjectByCallable::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerConfig::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(Helper::class)]
#[CoversClass(ReflectionMethodByDefinition::class)]
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
