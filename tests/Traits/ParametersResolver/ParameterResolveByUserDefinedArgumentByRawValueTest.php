<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver;

use Kaspi\DiContainer\Traits\ParametersResolverTrait;
use Kaspi\DiContainer\Traits\PsrContainerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diValue
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait
 * @covers \Kaspi\DiContainer\Traits\PsrContainerTrait
 *
 * @internal
 */
class ParameterResolveByUserDefinedArgumentByRawValueTest extends TestCase
{
    // 🔥 Test Trait 🔥
    use ParametersResolverTrait;
    // 🧨 need for abstract method getContainer.
    use PsrContainerTrait;

    public function testUserDefinedArgumentAsArrayNonVariadicSuccess(): void
    {
        $fn = static fn (iterable $iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->arguments = [
            'iterator' => ['aaa', 'bbb', 'ccc'],
        ];

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertEquals(['aaa', 'bbb', 'ccc'], $res);
    }

    public function testUserDefinedArgumentAsArrayVariadicSuccess(): void
    {
        $fn = static fn (iterable ...$iterator) => $iterator;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->arguments = [
            'iterator' => [
                ['aaa', 'bbb', 'ccc'],
                ['ddd', 'eee', 'fff'],
            ],
        ];

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertCount(2, $res);

        $this->assertEquals(['aaa', 'bbb', 'ccc'], $res[0]);
        $this->assertEquals(['ddd', 'eee', 'fff'], $res[1]);
    }

    public function testUserDefinedArgumentAsStringVariadicSuccess(): void
    {
        $fn = static fn (string ...$word) => $word;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->arguments = [
            'word' => ['aaa', 'bbb', 'ccc'],
        ];

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertCount(3, $res);

        $this->assertEquals('aaa', $res[0]);
        $this->assertEquals('bbb', $res[1]);
        $this->assertEquals('ccc', $res[2]);
    }

    public function testUserDefinedArgumentAsStdClass(): void
    {
        $fn = static fn (\stdClass $o) => $o;
        $this->reflectionParameters = (new \ReflectionFunction($fn))->getParameters();

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->expects($this->never())->method('get');
        $this->setContainer($mockContainer);

        // 🚩 test data
        $this->arguments = [
            'o' => new \stdClass(),
        ];

        $res = \call_user_func_array($fn, $this->resolveParameters());

        $this->assertInstanceOf(\stdClass::class, $res);
    }
}
