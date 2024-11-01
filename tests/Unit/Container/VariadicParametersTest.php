<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Classes\VariadicClassArguments;
use Tests\Fixtures\Classes\VariadicParameterA;
use Tests\Fixtures\Classes\VariadicParameterB;
use Tests\Fixtures\Classes\VariadicParameterInterface;
use Tests\Fixtures\Classes\VariadicSimpleArguments;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\ArgumentsForResolvingTrait
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
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

    public function testCallMethodClassWithStaticMethodWithSimpleParameters(): void
    {
        $container = (new DiContainerFactory())->make();

        $res = $container->call([VariadicSimpleArguments::class, 'sayStatic'], ['word' => ['welcome', 'to', 'func']]);

        $this->assertEquals('welcome_to_func', $res);
    }

    public function testVariadicParametersAsClass(): void
    {
        $container = (new DiContainerFactory())->make([
            VariadicParameterInterface::class => VariadicParameterA::class,
        ]);
        $class = $container->get(VariadicClassArguments::class);

        $this->assertInstanceOf(VariadicClassArguments::class, $class);
        $this->assertCount(1, $class->getParameters());
        $this->assertInstanceOf(VariadicParameterA::class, \current($class->getParameters()));
    }

    public function testVariadicParametersAsClassManyItems(): void
    {
        $container = (new DiContainerFactory())->make([
            'paramA' => VariadicParameterA::class,
            'paramB' => VariadicParameterB::class,
            VariadicClassArguments::class => [
                DiContainerInterface::ARGUMENTS => [
                    // @todo My be if value is class - resolve as get(class-name)?
                    'parameter' => [
                        '@paramB',
                        '@paramA',
                        '@paramA',
                        '@paramB',
                    ],
                ],
            ],
        ]);
        $class = $container->get(VariadicClassArguments::class);

        $this->assertInstanceOf(VariadicClassArguments::class, $class);
        $this->assertCount(4, $class->getParameters());

        $params = $class->getParameters();

        $this->assertInstanceOf(VariadicParameterB::class, \current($params));
        $this->assertInstanceOf(VariadicParameterA::class, \next($params));
        $this->assertInstanceOf(VariadicParameterA::class, \next($params));
        $this->assertInstanceOf(VariadicParameterB::class, \next($params));
    }
}
