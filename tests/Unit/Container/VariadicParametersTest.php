<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Classes\VariadicSimpleArguments;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\ArgumentsForResolvingTrait
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionSimple
 *
 * @internal
 */
class VariadicParametersTest extends TestCase
{
    public function testVariadicSimpleParametersInConstructor(): void
    {
        $c = (new DiContainerFactory())->make([
            'ref1' => 'fifth',
            VariadicSimpleArguments::class => [
                DiContainerInterface::ARGUMENTS => [
                    'word' => [
                        'first',
                        'second',
                        'third',
                        'fourth',
                        '@ref1', // reference to other container-id
                    ],
                ],
            ],
        ]);

        $this->assertEquals(['first', 'second', 'third', 'fourth', 'fifth'], $c->get(VariadicSimpleArguments::class)->sayHello);
    }
}
