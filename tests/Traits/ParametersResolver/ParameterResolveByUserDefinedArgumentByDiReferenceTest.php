<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\PsrContainerTrait
 *
 * @internal
 */
class ParameterResolveByUserDefinedArgumentByDiReferenceTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ParametersResolverTrait;
    use BindArgumentsTrait;
    // 🧨 need for abstract method getContainer.
    use PsrContainerTrait;

    public function testUserDefinedArgumentBydiGetNonVariadicSuccessByName(): void
    {
        $fn = static fn (\ArrayIterator $iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with('services.icon-iterator')
            ->willReturn(new \ArrayIterator(array: ['🚀', '🔥']))
        ;
        $this->setContainer($mockContainer);
        // 🚩 test data (inject arguments for ParameterResolverTrait)
        $this->arguments = $this->bindArguments(
            iterator: diGet('services.icon-iterator'),
        )
            ->getBindArguments()
        ;

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(\ArrayIterator::class, $res);
        $this->assertEquals(['🚀', '🔥'], $res->getArrayCopy());
    }

    public function testUserDefinedArgumentByManydiGetVariadicByName(): void
    {
        $fn = static fn (string $name, \ArrayIterator ...$iterator) => [$name, $iterator];
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
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
        $this->arguments = $this->bindArguments(
            iterator: [
                diGet('services.icon-iterator.two'),
                diGet('services.icon-iterator.one'),
            ],
            name: 'Piter'
        )
            ->getBindArguments()
        ;

        [$name, $res] = \call_user_func_array($fn, $this->resolveParameters());

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
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
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
        $this->arguments = $this->bindArguments(
            'Ivan',
            diGet('services.icon-iterator.two'),
            diGet('services.icon-iterator.one'),
        )
            ->getBindArguments()
        ;

        [$name, $res] = \call_user_func_array($fn, $this->resolveParameters());

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
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
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
        $this->arguments = $this->bindArguments(
            diGet('services.icon-iterator.one'),
            diGet('services.icon-iterator.two')
        )
            ->getBindArguments()
        ;

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertCount(2, $res);

        $this->assertInstanceOf(\ArrayIterator::class, $res[0]);
        $this->assertEquals(['🚀'], $res[0]->getArrayCopy());

        $this->assertInstanceOf(\ArrayIterator::class, $res[1]);
        $this->assertEquals(['🔥'], $res[1]->getArrayCopy());
    }
}
