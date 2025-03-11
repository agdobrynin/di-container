<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Traits\BindArgumentsTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;

use function call_user_func_array;

/**
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 * @covers \Kaspi\DiContainer\Traits\BindArgumentsTrait
 * @covers \Kaspi\DiContainer\Traits\DiContainerTrait
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 *
 * @internal
 */
class ParameterResolveByUserDefinedArgumentBySimpleDiDefinitionTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use BindArgumentsTrait;
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use DiContainerTrait;

    public function testUserDefinedArgumentByDiValueNonVariadicSuccess(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $mockContainer->expects($this->once())
            ->method('getConfig')
            ->willReturn(new DiContainerConfig())
        ;
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(iterator: ['aaa', 'bbb', 'ccc']);

        $res = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertEquals(['aaa', 'bbb', 'ccc'], $res);
    }

    public function testUserDefinedArgumentByManyDiValueVariadicSuccess(): void
    {
        $fn = static fn (iterable ...$iterator) => $iterator;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            iterator: [
                ['aaa', 'bbb', 'ccc'],
                ['ddd', 'eee', 'fff'],
            ]
        );

        $res = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertCount(2, $res);

        $this->assertEquals(['aaa', 'bbb', 'ccc'], $res[0]);
        $this->assertEquals(['ddd', 'eee', 'fff'], $res[1]);
    }

    public function testUserDefinedArgumentVariadicOneByName(): void
    {
        $fn = static fn (string ...$str) => $str;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            str: 'hi my darling'
        );

        $res = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertCount(1, $res);

        $this->assertEquals('hi my darling', $res[0]);
    }

    public function testUserDefinedArgumentByIndexVariadicSuccess(): void
    {
        $fn = static fn (iterable ...$iterator) => $iterator;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            ['aaa', 'bbb', 'ccc'],
            ['ddd', 'eee', 'fff'],
        );

        $res = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertCount(2, $res);

        $this->assertEquals(['aaa', 'bbb', 'ccc'], $res[0]);
        $this->assertEquals(['ddd', 'eee', 'fff'], $res[1]);
    }

    public function testUserDefinedArgumentByDefinitionValueByName(): void
    {
        $fn = static fn (array $words) => $words;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        // 🚩 test data
        $this->bindArguments(words: new DiDefinitionValue(['hello', 'world', '!']));
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('getConfig')
            ->willReturn(new DiContainerConfig())
        ;
        $this->setContainer($mockContainer);

        $res = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertCount(3, $res);
        $this->assertEquals(['hello', 'world', '!'], $res);
    }

    public function testUserDefinedArgumentByDefinitionValueByIndex(): void
    {
        $fn = static fn (array $words) => $words;
        $reflectionParameters = (new ReflectionFunction($fn))->getParameters();

        // 🚩 test data
        $this->bindArguments(new DiDefinitionValue(['hello', 'world', '!']));
        $mockContainer = $this->createMock(DiContainerInterface::class);
        $mockContainer->expects($this->once())
            ->method('getConfig')
            ->willReturn(new DiContainerConfig())
        ;
        $this->setContainer($mockContainer);

        $res = call_user_func_array($fn, $this->resolveParameters($this->getBindArguments(), $reflectionParameters));

        $this->assertCount(3, $res);
        $this->assertEquals(['hello', 'world', '!'], $res);
    }
}
