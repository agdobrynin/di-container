<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Fixtures\Classes\ClassWithEmptyType;
use Tests\Fixtures\Classes\ClassWithUnionType;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory::makeFromReflection
 * @covers \Kaspi\DiContainer\Attributes\Inject::makeFromReflection
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerDefinition
 * @covers \Kaspi\DiContainer\DiContainerFactory::make
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

    // @todo add test for union type in constructor with success resolving.
}
