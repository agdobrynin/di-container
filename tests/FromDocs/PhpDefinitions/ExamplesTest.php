<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\Sum;
use Tests\FromDocs\PhpDefinitions\Fixtures\SumInterface;

use function Kaspi\DiContainer\diAutowire;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class ExamplesTest extends TestCase
{
    public function testExample1(): void
    {
        $definition = [
            SumInterface::class => diAutowire(Sum::class)
                ->addArgument('init', 50),
            diAutowire(Sum::class)
                ->addArgument('init', 10),
        ];

        $c = (new DiContainerFactory())->make($definition);

        $this->assertEquals(50, $c->get(SumInterface::class)->getInit());
        $this->assertEquals(10, $c->get(Sum::class)->getInit());
    }
}
