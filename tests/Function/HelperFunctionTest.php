<?php

declare(strict_types=1);

namespace Tests\Function;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameterRuntime;
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use Tests\Function\Fixtures\AnyClass;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diGet;
use function Kaspi\DiContainer\diParameter;
use function Kaspi\DiContainer\diParameterRuntime;
use function Kaspi\DiContainer\diProxyClosure;
use function Kaspi\DiContainer\diTaggedAs;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diAutowire')]
#[CoversFunction('\Kaspi\DiContainer\diCallable')]
#[CoversFunction('\Kaspi\DiContainer\diGet')]
#[CoversFunction('\Kaspi\DiContainer\diProxyClosure')]
#[CoversFunction('\Kaspi\DiContainer\diTaggedAs')]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionCallable::class)]
#[CoversClass(DiDefinitionGet::class)]
#[CoversClass(DiDefinitionProxyClosure::class)]
#[CoversClass(DiDefinitionTaggedAs::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(Helper::class)]
#[CoversClass(DiDefinitionParameter::class)]
#[CoversFunction('Kaspi\DiContainer\diParameter')]
#[CoversClass(DiDefinitionParameterRuntime::class)]
#[CoversFunction('Kaspi\DiContainer\diParameterRuntime')]
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

    #[DataProvider('dataProviderReflectionFn')]
    public function testFunctionName(ReflectionFunctionAbstract $fn, string $expectRegExp): void
    {
        self::assertMatchesRegularExpression($expectRegExp, Helper::functionName($fn));
    }

    public static function dataProviderReflectionFn(): Generator
    {
        yield 'in class method' => [
            (new ReflectionClass(AnyClass::class))->getMethod('foo'),
            '/^Tests\\\Function\\\Fixtures\\\AnyClass::foo\(\)$/',
        ];

        yield 'in class constructor' => [
            (new ReflectionClass(AnyClass::class))->getConstructor(),
            '/^Tests\\\Function\\\Fixtures\\\AnyClass::__construct\(\)$/',
        ];

        yield 'in user defined function' => [
            new ReflectionFunction('Tests\Function\Fixtures\bar'),
            '/^Tests\\\Function\\\Fixtures\\\bar\(\)$/',
        ];

        yield 'in build-in function' => [
            new ReflectionFunction('\log'),
            '/^log\(\)$/',
        ];

        yield 'closure function' => [
            new ReflectionFunction(require __DIR__.'/Fixtures/closure.php'),
            '/{closure.+tests\/Function\/Fixtures\/closure.php:7}\(\)/',
        ];
    }

    #[TestWith(['', ''])]
    #[TestWith(['foo', 'foo'])]
    public function testDiParam(string $name, string $expectDefinition): void
    {
        self::assertEquals($expectDefinition, diParameter($name)->getDefinition());
    }

    #[TestWith(['foo', 'foo', 'msg', 'msg'])]
    #[TestWith(['', '', null, 'Did you forget to define it?'])]
    public function testDiParameterRuntime(string $name, string $expectDefinition, ?string $message, ?string $expectMessage): void
    {
        $p = diParameterRuntime($name, $message);

        self::assertEquals($expectDefinition, $p->getDefinition());
        self::assertEquals($expectMessage, $p->getMessage());
    }
}
