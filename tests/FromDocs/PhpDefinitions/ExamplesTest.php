<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\Sum;
use Tests\FromDocs\PhpDefinitions\Fixtures\SumInterface;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\BuildArguments
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 *
 * @internal
 */
class ExamplesTest extends TestCase
{
    public function testExample1(): void
    {
        $definition = [
            SumInterface::class => diAutowire(Sum::class)
                ->bindArguments(init: 50),
            diAutowire(Sum::class)
                ->bindArguments(init: 10),
        ];

        $c = (new DiContainerFactory())->make($definition);

        $this->assertEquals(50, $c->get(SumInterface::class)->getInit());
        $this->assertEquals(10, $c->get(Sum::class)->getInit());
    }

    public function testExample2(): void
    {
        $container = (new DiContainerFactory())->make();

        $sum1 = (new DiDefinitionAutowire(Sum::class))
            ->bindArguments(init: 50)
            ->setContainer($container)
            ->invoke()
        ;

        $sum2 = (new DiDefinitionAutowire(Sum::class))
            ->bindArguments(init: 20)
            ->setContainer($container)
            ->invoke()
        ;

        $this->assertNotSame($sum1, $sum2);
        $this->assertEquals(50, $sum1->getInit());
        $this->assertEquals(20, $sum2->getInit());
    }
}
