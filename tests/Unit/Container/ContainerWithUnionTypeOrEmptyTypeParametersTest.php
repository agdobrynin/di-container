<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Fixtures\Attributes\ClassC;
use Tests\Fixtures\Attributes\ClassD;
use Tests\Fixtures\Classes\ClassA;
use Tests\Fixtures\Classes\ClassB;
use Tests\Fixtures\Classes\ClassWithEmptyType;
use Tests\Fixtures\Classes\ClassWithUnionType;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory::makeFromReflection
 * @covers \Kaspi\DiContainer\Attributes\Inject
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory::make
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionClosure
 *
 * @internal
 */
class ContainerWithUnionTypeOrEmptyTypeParametersTest extends TestCase
{
    public function testEmptyTypeHint(): void
    {
        $c = (new DiContainerFactory())->make([
            'dependency' => static fn () => new \ArrayIterator(),
        ]);

        $this->assertInstanceOf(\ArrayIterator::class, $c->get(ClassWithEmptyType::class)->dependency);
    }

    public function testCloserArg(): void
    {
        $c = (new DiContainerFactory())->make([
            ClassWithEmptyType::class => [
                DiContainerInterface::ARGUMENTS => [
                    'dependency' => static fn () => new \ArrayIterator(),
                ],
            ],
        ]);

        $this->assertInstanceOf(\Closure::class, $c->get(ClassWithEmptyType::class)->dependency);
        $this->assertInstanceOf(\ArrayIterator::class, ($c->get(ClassWithEmptyType::class)->dependency)());
    }

    public function testEmptyTypeHintByDefinitionConstructor(): void
    {
        $c = (new DiContainerFactory())->make([
            ClassWithEmptyType::class => ['arguments' => ['dependency' => new \stdClass()]],
        ]);

        $this->assertInstanceOf(\stdClass::class, $c->get(ClassWithEmptyType::class)->dependency);
    }

    public function testUnionTypeHint(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Unresolvable dependency');

        (new DiContainerFactory())->make()->get(ClassWithUnionType::class);
    }

    public function testUnionTypeSuccess(): void
    {
        $class = (new DiContainerFactory())->make([
            ClassWithUnionType::class => [
                DiContainerInterface::ARGUMENTS => [
                    'dependency' => '@'.\ReflectionMethod::class,
                ],
            ],
            \ReflectionMethod::class => [
                DiContainerInterface::ARGUMENTS => [
                    'objectOrMethod' => $this,
                    'method' => 'testUnionTypeSuccess',
                ],
            ],
        ])->get(ClassWithUnionType::class);

        $this->assertInstanceOf(ClassWithUnionType::class, $class);
        $this->assertInstanceOf(\ReflectionMethod::class, $class->dependency);
        $this->assertEquals(self::class, $class->dependency->class);
        $this->assertEquals('testUnionTypeSuccess', $class->dependency->getName());
    }

    public function testUnionTypeByAttribute(): void
    {
        $class = (new DiContainerFactory())->make()
            ->get(\Tests\Fixtures\Attributes\ClassWithUnionType::class)
        ;

        $this->assertInstanceOf(\Tests\Fixtures\Attributes\ClassWithUnionType::class, $class);
        $this->assertInstanceOf(\ReflectionMethod::class, $class->dependency);
        $this->assertEquals(self::class, $class->dependency->class);
        $this->assertEquals('testUnionTypeByAttribute', $class->dependency->getName());
    }

    public function testUnionTypeTwoClassesWithoutDefinition(): void
    {
        $class = (new DiContainerFactory())->make()->get(ClassA::class);

        $this->assertInstanceOf(ClassB::class, $class->var);
    }

    public function testUnionTypeTwoClassesWithDefinition(): void
    {
        $class = (new DiContainerFactory())->make([
            ClassA::class => [
                DiContainerInterface::ARGUMENTS => [
                    'var' => ['one', 'two', 'three'],
                ],
            ],
        ])->get(ClassA::class);

        $this->assertEquals(['one', 'two', 'three'], $class->var);
    }

    public function testUnionTypeByInjectDefault(): void
    {
        $class = (new DiContainerFactory())->make()->get(ClassC::class);

        $this->assertInstanceOf(\Tests\Fixtures\Attributes\ClassB::class, $class->var);
    }

    public function testUnionTypeByInjectWithDefinition(): void
    {
        $class = (new DiContainerFactory())->make()->get(ClassD::class);

        $this->assertInstanceOf(\Tests\Fixtures\Attributes\ClassB::class, $class->var);
    }
}
