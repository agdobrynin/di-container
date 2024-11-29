<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\PsrContainerTrait
 *
 * @internal
 */
class ParameterResolveByUserDefinedArgumentByDiReferenceTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use PsrContainerTrait;

    public function testUserDefinedArgumentByDiReferenceNonVariadicSuccess(): void
    {
        $fn = static fn (\ArrayIterator $iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('get')->with('services.icon-iterator')
            ->willReturn(new \ArrayIterator(array: ['🚀', '🔥']))
        ;
        $this->setContainer($mockContainer);
        // 🚩 test data
        $this->arguments = [
            'iterator' => diReference('services.icon-iterator'),
        ];

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(\ArrayIterator::class, $res);
        $this->assertEquals(['🚀', '🔥'], $res->getArrayCopy());
    }

    public function testUserDefinedArgumentByManyDiReferenceVariadic(): void
    {
        $fn = static fn (\ArrayIterator ...$iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->atMost(2))
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
        $this->arguments = [
            'iterator' => [
                diReference('services.icon-iterator.two'),
                diReference('services.icon-iterator.one'),
            ],
        ];

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertCount(2, $res);

        $this->assertInstanceOf(\ArrayIterator::class, $res[0]);
        $this->assertEquals(['🚀'], $res[0]->getArrayCopy());

        $this->assertInstanceOf(\ArrayIterator::class, $res[1]);
        $this->assertEquals(['🔥'], $res[1]->getArrayCopy());
    }

    public function testUserDefinedArgumentByOneDiReferenceVariadic(): void
    {
        $fn = static fn (\ArrayIterator ...$iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->atMost(2))
            ->method('get')
            ->with('services.icon-iterator')
            ->willReturn(
                [
                    new \ArrayIterator(array: ['🚀']),
                    new \ArrayIterator(array: ['🔥']),
                ]
            )
        ;

        $this->setContainer($mockContainer);
        // 🚩 test data
        $this->arguments = [
            'iterator' => diReference('services.icon-iterator'),
        ];

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertCount(2, $res);

        $this->assertInstanceOf(\ArrayIterator::class, $res[0]);
        $this->assertEquals(['🚀'], $res[0]->getArrayCopy());

        $this->assertInstanceOf(\ArrayIterator::class, $res[1]);
        $this->assertEquals(['🔥'], $res[1]->getArrayCopy());
    }
}
