<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Traits\ParametersResolver\Fixtures\SuperClass;

use function Kaspi\DiContainer\diValue;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diValue
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\ParameterTypeByReflectionTrait
 * @covers \Kaspi\DiContainer\Traits\PsrContainerTrait
 * @covers \Kaspi\DiContainer\Traits\UseAttributeTrait
 *
 * @internal
 */
class ParameterResolveByUserDefinedArgumentByRawValueTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use BindArgumentsTrait;
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use PsrContainerTrait;

    public function testUserDefinedArgumentAsArrayNonVariadicByIndexSuccess(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(['aaa', 'bbb', 'ccc']);

        $res = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertEquals(['aaa', 'bbb', 'ccc'], $res);
    }

    public function testUserDefinedArgumentAsArrayNonVariadicByNameSuccess(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(iterator: ['aaa', 'bbb', 'ccc']);

        $res = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertEquals(['aaa', 'bbb', 'ccc'], $res);
    }

    public function testUserDefinedArgumentAsArrayVariadicByIndexSuccess(): void
    {
        $fn = static fn (string $val, iterable ...$iterator) => [$val, $iterator];
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects(self::never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            'my way',
            ['aaa', 'bbb', 'ccc'],
            ['ddd', 'eee', 'fff'],
        );

        [$str ,$iter] = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertEquals('my way', $str);
        $this->assertCount(2, $iter);
        $this->assertEquals(['aaa', 'bbb', 'ccc'], $iter[0]);
        $this->assertEquals(['ddd', 'eee', 'fff'], $iter[1]);
    }

    public function testUserDefinedArgumentAsArrayVariadicByNameSuccess(): void
    {
        $fn = static fn (iterable ...$iterator) => $iterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(iterator: [
            ['aaa', 'bbb', 'ccc'],
            ['ddd', 'eee', 'fff'],
        ]);

        $res = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertCount(2, $res);

        $this->assertEquals(['aaa', 'bbb', 'ccc'], $res[0]);
        $this->assertEquals(['ddd', 'eee', 'fff'], $res[1]);
    }

    public function testUserDefinedArgumentOneVariadicByNameWrappedByDiValue(): void
    {
        $fn = static fn (iterable ...$iterator) => $iterator;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(iterator: diValue(['aaa', 'bbb', 'ccc']));

        $res = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertCount(1, $res);

        $this->assertEquals(['aaa', 'bbb', 'ccc'], $res[0]);
    }

    public function testUserDefinedArgumentAsStringVariadicByIndexSuccess(): void
    {
        $fn = static fn (string ...$word) => $word;
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments('aaa', 'bbb', 'ccc');

        $res = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertCount(3, $res);

        $this->assertEquals('aaa', $res[0]);
        $this->assertEquals('bbb', $res[1]);
        $this->assertEquals('ccc', $res[2]);
    }

    public function testUserDefinedArgumentAsStringVariadicByNameSuccess(): void
    {
        $fn = static fn (array $numbers, string ...$word) => [$numbers, $word];
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data, unsorted parameter names
        $this->bindArguments(word: ['aaa', 'bbb', 'ccc'], numbers: [1_000, 10_000]);

        [$numbers, $word] = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertCount(2, $numbers);
        $this->assertEquals(1_000, $numbers[0]);
        $this->assertEquals(10_000, $numbers[1]);

        $this->assertCount(3, $word);

        $this->assertEquals('aaa', $word[0]);
        $this->assertEquals('bbb', $word[1]);
        $this->assertEquals('ccc', $word[2]);
    }

    public function testUserDefinedArgumentAsStdClassByIndexAndName(): void
    {
        $fn = static fn (string $str, SuperClass $super, array $numbers) => [$str, $super, $numbers];
        $reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->once())->method('get')
            ->with(SuperClass::class)
            ->willReturn(new SuperClass())
        ;
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments('Hello', numbers: [1_000, 10_000]);

        [$str, $super, $numbers] = \call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertEquals('Hello', $str);
        $this->assertEquals([1000, 10000], $numbers);
        $this->assertInstanceOf(SuperClass::class, $super);
    }
}
