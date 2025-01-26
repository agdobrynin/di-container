<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 *
 * @internal
 */
class ParameterResolveByUserDefinedArgumentByDiReferenceTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ParametersResolverTrait;
    use BindArgumentsTrait;
    // 🧨 need for abstract method getContainer.
    use DiContainerTrait;

    public function testUserDefinedArgumentBydiGetNonVariadicSuccessByName(): void
    {
        $fn = static fn (\ArrayIterator $iterator) => $iterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with('services.icon-iterator')
            ->willReturn(new \ArrayIterator(array: ['🚀', '🔥']))
        ;
        $this->setContainer($mockContainer);
        // 🚩 test data (inject arguments for ParameterResolverTrait)
        $this->bindArguments(
            iterator: diGet('services.icon-iterator'),
        );

        $res = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertInstanceOf(\ArrayIterator::class, $res);
        $this->assertEquals(['🚀', '🔥'], $res->getArrayCopy());
    }

    public function testUserDefinedArgumentByManydiGetVariadicByName(): void
    {
        $fn = static fn (string $name, \ArrayIterator ...$iterator) => [$name, $iterator];
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::exactly(2))
            ->method('get')
            ->with($this->logicalOr(
                'services.icon-iterator.one',
                'services.icon-iterator.two'
            ))
            ->willReturn(
                new \ArrayIterator(array: ['🚀']),
                new \ArrayIterator(array: ['🔥']),
            )
        ;

        $this->setContainer($mockContainer);
        // 🚩 test data
        $this->bindArguments(
            iterator: [
                diGet('services.icon-iterator.two'),
                diGet('services.icon-iterator.one'),
            ],
            name: 'Piter'
        );

        [$name, $res] = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertEquals('Piter', $name);
        $this->assertCount(2, $res);

        $this->assertInstanceOf(\ArrayIterator::class, $res[0]);
        $this->assertEquals(['🚀'], $res[0]->getArrayCopy());

        $this->assertInstanceOf(\ArrayIterator::class, $res[1]);
        $this->assertEquals(['🔥'], $res[1]->getArrayCopy());
    }

    public function testUserDefinedArgumentByManydiGetVariadicByIndex(): void
    {
        $fn = static fn (string $name, \ArrayIterator ...$iterator) => [$name, $iterator];
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::exactly(2))
            ->method('get')
            ->with(self::logicalOr(
                'services.icon-iterator.two',
                'services.icon-iterator.one'
            ))
            ->willReturn(
                new \ArrayIterator(array: ['🚀']),
                new \ArrayIterator(array: ['🔥']),
            )
        ;

        $this->setContainer($mockContainer);
        // 🚩 test data
        $this->bindArguments(
            'Ivan',
            diGet('services.icon-iterator.two'),
            diGet('services.icon-iterator.one'),
        );

        [$name, $res] = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertEquals('Ivan', $name);
        $this->assertCount(2, $res);

        $this->assertInstanceOf(\ArrayIterator::class, $res[0]);
        $this->assertEquals(['🚀'], $res[0]->getArrayCopy());

        $this->assertInstanceOf(\ArrayIterator::class, $res[1]);
        $this->assertEquals(['🔥'], $res[1]->getArrayCopy());
    }

    public function testUserDefinedArgumentByOnediGetVariadicByIndex(): void
    {
        $fn = static fn (\ArrayIterator ...$iterator) => $iterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects(self::exactly(2))
            ->method('get')
            ->with(self::logicalOr(
                'services.icon-iterator.one',
                'services.icon-iterator.two'
            ))
            ->willReturn(
                new \ArrayIterator(array: ['🚀']),
                new \ArrayIterator(array: ['🔥']),
            )
        ;

        $this->setContainer($mockContainer);
        // 🚩 test data
        $this->bindArguments(
            diGet('services.icon-iterator.one'),
            diGet('services.icon-iterator.two')
        );

        $res = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertCount(2, $res);

        $this->assertInstanceOf(\ArrayIterator::class, $res[0]);
        $this->assertEquals(['🚀'], $res[0]->getArrayCopy());

        $this->assertInstanceOf(\ArrayIterator::class, $res[1]);
        $this->assertEquals(['🔥'], $res[1]->getArrayCopy());
    }
}
