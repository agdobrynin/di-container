<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\PsrContainerTrait
 *
 * @internal
 */
class ParameterResolveByUserDefinedArgumentBySimpleDiDefinitionTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use PsrContainerTrait;

    public function testUserDefinedArgumentByDiValueNonVariadicSuccess(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(iterator: ['aaa', 'bbb', 'ccc']);

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertEquals(['aaa', 'bbb', 'ccc'], $res);
    }

    public function testUserDefinedArgumentByManyDiValueVariadicSuccess(): void
    {
        $fn = static fn (iterable ...$iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            iterator: [
                ['aaa', 'bbb', 'ccc'],
                ['ddd', 'eee', 'fff'],
            ]
        );

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertCount(2, $res);

        $this->assertEquals(['aaa', 'bbb', 'ccc'], $res[0]);
        $this->assertEquals(['ddd', 'eee', 'fff'], $res[1]);
    }

    public function testUserDefinedArgumentByIndexVariadicSuccess(): void
    {
        $fn = static fn (iterable ...$iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->bindArguments(
            ['aaa', 'bbb', 'ccc'],
            ['ddd', 'eee', 'fff']
        );

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertCount(2, $res);

        $this->assertEquals(['aaa', 'bbb', 'ccc'], $res[0]);
        $this->assertEquals(['ddd', 'eee', 'fff'], $res[1]);
    }

    public function testUserDefinedArgumentByDefinitionValue(): void
    {
        $fn = static fn (array $words) => $words;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        // 🚩 test data
        $this->arguments = [
            'words' => new DiDefinitionValue(['hello', 'world', '!']),
        ];

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertCount(3, $res);
        $this->assertEquals(['hello', 'world', '!'], $res);
    }
}
