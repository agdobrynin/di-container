<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpDefinitions\Fixtures\Sum;
use Tests\FromDocs\PhpDefinitions\Fixtures\SumInterface;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversNothing]
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

        $c = (new DiContainerBuilder())
            ->addDefinitions($definition)
            ->build()
        ;

        self::assertEquals(50, $c->get(SumInterface::class)->getInit());
        self::assertEquals(10, $c->get(Sum::class)->getInit());
    }

    public function testExample2(): void
    {
        $container = (new DiContainerBuilder())->build();

        $sum1 = (new DiDefinitionAutowire(Sum::class))
            ->bindArguments(init: 50)
            ->setContainer($container)
            ->resolve($container)
        ;

        $sum2 = (new DiDefinitionAutowire(Sum::class))
            ->bindArguments(init: 20)
            ->setContainer($container)
            ->resolve($container)
        ;

        self::assertNotSame($sum1, $sum2);
        self::assertEquals(50, $sum1->getInit());
        self::assertEquals(20, $sum2->getInit());
    }
}
