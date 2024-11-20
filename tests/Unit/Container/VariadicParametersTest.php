<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Classes\Interfaces\VariadicParameterInterface;
use Tests\Fixtures\Classes\VariadicArguments;
use Tests\Fixtures\Classes\VariadicClassArgumentAsInterface;
use Tests\Fixtures\Classes\VariadicClassWithMethodArguments;
use Tests\Fixtures\Classes\VariadicParameterA;
use Tests\Fixtures\Classes\VariadicParameterB;
use Tests\Fixtures\Classes\VariadicParameterC;
use Tests\Fixtures\Classes\VariadicParameterRule;
use Tests\Fixtures\Classes\VariadicSimpleArguments;
use Tests\Fixtures\Classes\VariadicSimpleArrayArguments;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\InjectContext
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 *
 * @internal
 */
class VariadicParametersTest extends TestCase
{
    public function testVariadicSimpleParametersInConstructor(): void
    {
        $c = (new DiContainerFactory())->make([
            'ref1' => 'fifth',
            diAutowire(
                VariadicSimpleArguments::class,
                [
                    'word' => [
                        'first',
                        'second',
                        'third',
                        'fourth',
                        diReference('ref1'), // reference to other container-id
                    ],
                ]
            ),
        ]);

        $this->assertEquals(['first', 'second', 'third', 'fourth', 'fifth'], $c->get(VariadicSimpleArguments::class)->sayHello);
    }

    public function testVariadicSimpleParametersInConstructorOneParameter(): void
    {
        $c = (new DiContainerFactory())->make([
            diAutowire(VariadicSimpleArguments::class, ['word' => 'first']),
        ]);

        $this->assertEquals(['first'], $c->get(VariadicSimpleArguments::class)->sayHello);
    }

    public function testVariadicSimpleParametersInConstructorParameterAsArrayType(): void
    {
        $c = (new DiContainerFactory())->make([
            diAutowire(VariadicSimpleArrayArguments::class)
                ->addArgument(
                    'token',
                    [['start', 'end']], // if variadic argument type array - always use array wrapper.
                ),
        ]);

        $this->assertEquals([['start', 'end']], $c->get(VariadicSimpleArrayArguments::class)->tokens);
    }

    public function testVariadicSimpleParametersInConstructorParameterAsArrayResolvedByParamName(): void
    {
        $c = (new DiContainerFactory())->make([
            'token' => [['start', 'end'], ['go', 'finish']], // if variadic argument type array - always use array wrapper.
        ]);

        $this->assertEquals(['start', 'end'], $c->get(VariadicSimpleArrayArguments::class)->tokens[0]);
        $this->assertEquals(['go', 'finish'], $c->get(VariadicSimpleArrayArguments::class)->tokens[1]);
    }

    public function testCallMethodClassWithStaticMethodWithSimpleParameters(): void
    {
        $container = (new DiContainerFactory())->make();

        $res = $container->call([VariadicSimpleArguments::class, 'sayStatic'], ['word' => ['welcome', 'to', 'func']]);

        $this->assertEquals('welcome_to_func', $res);
    }

    public function testCallMethodClassWithNonStaticMethodWithSimpleParameters(): void
    {
        $container = (new DiContainerFactory())->make([
            VariadicSimpleArguments::class => [
                DiContainerInterface::ARGUMENTS => [
                    'word' => ['welcome', 'to', 'class'],
                ],
            ],
        ]);

        $variadic = $container->get(VariadicSimpleArguments::class);

        $this->assertEquals(['welcome', 'to', 'class'], $variadic->sayHello);

        $res = $container->call([$variadic, 'say'], ['word' => ['Hello', 'world', '!']]);

        $this->assertEquals('Hello_world_!', $res);
    }

    public function testCallMethodForClassWithConstructorAndMethodWithVariadicParam(): void
    {
        $container = (new DiContainerFactory())->make([
            'config.medals' => ['🥉', '🥇'],
            'ref1' => VariadicParameterB::class,
        ]);

        $paramC = $container->get(VariadicParameterC::class);

        $params = $container->call(
            [VariadicClassWithMethodArguments::class, 'getParameters'],
            [
                'parameter' => [
                    $paramC,
                    diReference('ref1'),
                    diReference(VariadicParameterA::class),
                ],
            ]
        );

        $this->assertCount(5, $params);
        $this->assertInstanceOf(VariadicParameterC::class, \current($params));
        $this->assertInstanceOf(VariadicParameterB::class, \next($params));
        $this->assertInstanceOf(VariadicParameterA::class, \next($params));
        $this->assertEquals('🥉', \next($params));
        $this->assertEquals('🥇', \next($params));
    }

    public function testVariadicParametersAsClass(): void
    {
        $container = (new DiContainerFactory())->make([
            VariadicParameterInterface::class => VariadicParameterC::class,
        ]);
        $class = $container->get(VariadicClassArgumentAsInterface::class);

        $this->assertInstanceOf(VariadicClassArgumentAsInterface::class, $class);
        $this->assertCount(1, $class->getParameters());
        $this->assertInstanceOf(VariadicParameterC::class, \current($class->getParameters()));
    }

    public function testVariadicParameterViaInterface(): void
    {
        $definitions = [
            'refC' => VariadicParameterC::class,
            diAutowire(
                VariadicClassArgumentAsInterface::class,
                [
                    'parameter' => [
                        diAutowire(VariadicParameterB::class),
                        diReference('refC'),
                        diAutowire(VariadicParameterA::class),
                        diReference('refC'),
                    ],
                ]
            ),
        ];

        $container = (new DiContainerFactory())->make($definitions);
        $class = $container->get(VariadicClassArgumentAsInterface::class);

        $this->assertInstanceOf(VariadicClassArgumentAsInterface::class, $class);

        $params = $class->getParameters();

        $this->assertCount(4, $params);

        $this->assertInstanceOf(VariadicParameterB::class, \reset($params));
        $this->assertInstanceOf(VariadicParameterC::class, \next($params));
        $this->assertInstanceOf(VariadicParameterA::class, \next($params));
        $this->assertInstanceOf(VariadicParameterC::class, \next($params));
    }

    public function testVariadicParametersAsClassManyItems(): void
    {
        $container = (new DiContainerFactory())->make([
            diAutowire(VariadicClassArgumentAsInterface::class)
                ->addArgument(
                    'parameter',
                    [
                        diReference(VariadicParameterB::class),
                        diReference(VariadicParameterA::class),
                        diReference(VariadicParameterA::class),
                        diReference(VariadicParameterB::class),
                    ]
                ),
        ]);
        $class = $container->get(VariadicClassArgumentAsInterface::class);

        $this->assertInstanceOf(VariadicClassArgumentAsInterface::class, $class);
        $this->assertCount(4, $class->getParameters());

        $params = $class->getParameters();

        $this->assertInstanceOf(VariadicParameterB::class, \current($params));
        $this->assertInstanceOf(VariadicParameterA::class, \next($params));
        $this->assertInstanceOf(VariadicParameterA::class, \next($params));
        $this->assertInstanceOf(VariadicParameterB::class, \next($params));
    }

    public function testVariadicArgumentByClass(): void
    {
        $container = (new DiContainerFactory())->make();

        $class = $container->get(VariadicArguments::class);

        $this->assertCount(1, $class->getRules());
        $this->assertInstanceOf(VariadicParameterRule::class, \current($class->getRules()));
    }

    public function testVariadicArgumentWithNullable(): void
    {
        $container = (new DiContainerFactory())->make([
            VariadicArguments::class => [
                DiContainerInterface::ARGUMENTS => ['rule' => null],
            ],
        ]);

        $class = $container->get(VariadicArguments::class);

        $this->assertNull($class->getRules());
    }
}
