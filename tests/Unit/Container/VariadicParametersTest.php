<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Classes\VariadicArguments;
use Tests\Fixtures\Classes\VariadicClassArgumentAsInterface;
use Tests\Fixtures\Classes\VariadicClassWithMethodArguments;
use Tests\Fixtures\Classes\VariadicParameterA;
use Tests\Fixtures\Classes\VariadicParameterB;
use Tests\Fixtures\Classes\VariadicParameterC;
use Tests\Fixtures\Classes\VariadicParameterInterface;
use Tests\Fixtures\Classes\VariadicParameterRule;
use Tests\Fixtures\Classes\VariadicSimpleArguments;
use Tests\Fixtures\Classes\VariadicSimpleArrayArguments;

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

    public function testVariadicSimpleParametersInConstructorOneParameter(): void
    {
        $c = (new DiContainerFactory())->make([
            VariadicSimpleArguments::class => [
                DiContainerInterface::ARGUMENTS => [
                    'word' => 'first',
                ],
            ],
        ]);

        $this->assertEquals(['first'], $c->get(VariadicSimpleArguments::class)->sayHello);
    }

    public function testVariadicSimpleParametersInConstructorParameterAsArrayType(): void
    {
        $c = (new DiContainerFactory())->make([
            VariadicSimpleArrayArguments::class => [
                DiContainerInterface::ARGUMENTS => [
                    'token' => [['start', 'end']], // if variadic argument type array - always use array wrapper.
                ],
            ],
        ]);

        $this->assertEquals([['start', 'end']], $c->get(VariadicSimpleArrayArguments::class)->tokens);
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
            'ref2' => VariadicParameterA::class,
        ]);

        $paramC = $container->get(VariadicParameterC::class);

        $params = $container->call(
            [VariadicClassWithMethodArguments::class, 'getParameters'],
            ['parameter' => [$paramC, '@ref1', '@ref2']]
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
            'ref1' => VariadicParameterC::class,
            'ref2' => VariadicParameterA::class,
            'ref3' => VariadicParameterB::class,
            VariadicClassArgumentAsInterface::class => [
                DiContainerInterface::ARGUMENTS => [
                    'parameter' => ['@ref3', '@ref1', '@ref2', '@ref1'],
                ],
            ],
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
            'paramA' => VariadicParameterA::class,
            'paramB' => VariadicParameterB::class,
            VariadicClassArgumentAsInterface::class => [
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
        $class = $container->get(VariadicClassArgumentAsInterface::class);

        $this->assertInstanceOf(VariadicClassArgumentAsInterface::class, $class);
        $this->assertCount(4, $class->getParameters());

        $params = $class->getParameters();

        $this->assertInstanceOf(VariadicParameterB::class, \current($params));
        $this->assertInstanceOf(VariadicParameterA::class, \next($params));
        $this->assertInstanceOf(VariadicParameterA::class, \next($params));
        $this->assertInstanceOf(VariadicParameterB::class, \next($params));
    }

    public function testVariadicArguentByClass(): void
    {
        $container = (new DiContainerFactory())->make();

        $class = $container->get(VariadicArguments::class);

        $this->assertCount(1, $class->getRules());
        $this->assertInstanceOf(VariadicParameterRule::class, \current($class->getRules()));
    }
}
