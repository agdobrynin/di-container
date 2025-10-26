<?php

declare(strict_types=1);

namespace Tests\Function;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diGet;
use function Kaspi\DiContainer\diProxyClosure;
use function Kaspi\DiContainer\diTaggedAs;
use function Kaspi\DiContainer\functionNameByParameter;

/**
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\diProxyClosure
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\functionNameByParameter
 *
 * @internal
 */
class HelperFunctionTest extends TestCase
{
    public function testFunctiondiGet(): void
    {
        $def = diGet('ok');

        $this->assertInstanceOf(DiDefinitionGet::class, $def);
        $this->assertEquals('ok', $def->getDefinition());
    }

    public function testFunctionDiCallable(): void
    {
        $def = diCallable(static fn () => 'ok', true);

        $this->assertInstanceOf(DiDefinitionCallable::class, $def);
        $this->assertTrue($def->isSingleton());
    }

    public function testFunctionDiAutowire(): void
    {
        $def = diAutowire(self::class, true);

        $this->assertInstanceOf(DiDefinitionAutowire::class, $def);
        $this->assertTrue($def->isSingleton());
    }

    public function testFunctionDiProxyClosure(): void
    {
        $def = diProxyClosure(self::class);

        $this->assertInstanceOf(DiDefinitionProxyClosure::class, $def);
    }

    public function testFunctionDiTaggedAsWithDefaultParams(): void
    {
        $def = diTaggedAs('tags.security.voters');

        $this->assertInstanceOf(DiDefinitionTaggedAs::class, $def);
    }

    public function testFunctionDiTaggedAsWithAllParams(): void
    {
        $def = diTaggedAs('tags.security.voters', false, 'getPriorityForCollection', true);

        $this->assertInstanceOf(DiDefinitionTaggedAs::class, $def);
    }

    /**
     * @dataProvider dataProviderReflectionParameter
     */
    public function testFunctionNameByParameter(ReflectionParameter $parameter, string $expect): void
    {
        self::assertStringContainsString($expect, functionNameByParameter($parameter));
    }

    public function dataProviderReflectionParameter(): Generator
    {
        yield 'in class method' => [
            (new ReflectionClass(AnyClass::class))->getMethod('foo')->getParameters()[0],
            'Tests\Function\AnyClass::foo()',
        ];

        yield 'in class constructor' => [
            (new ReflectionClass(AnyClass::class))->getConstructor()->getParameters()[0],
            'Tests\Function\AnyClass::__construct()',
        ];

        yield 'in user defined function' => [
            (new ReflectionFunction('Tests\Function\bar'))->getParameters()[0],
            'Tests\Function\bar()',
        ];

        yield 'in build-in function' => [
            (new ReflectionFunction('\log'))->getParameters()[0],
            'log()',
        ];

        $expect = 'Tests\Function\{closure}()';

        if (PHP_VERSION_ID >= 80400) {
            $expect = 'Tests\Function\HelperFunctionTest::{closure:Tests\Function\HelperFunctionTest::dataProviderReflectionParameter()';
        }

        yield 'closure function' => [
            (new ReflectionFunction(fn (string $bar) => $bar.'*'))->getParameters()[0],
            $expect,
        ];
    }
}

// Fixtures for method testFunctionNameByParameter.
class AnyClass
{
    public function __construct(private string $foo) {}

    public function foo(string $baz): void {}
}

function bar(string $param): string
{
    return 'foo + '.$param;
}
