<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Generator;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\Attributes\Raw\Fixtures\Foo;
use Tests\Attributes\Raw\Fixtures\MyDiFactory;

/**
 * @internal
 */
#[CoversClass(DiFactory::class)]
class DiFactoryTest extends TestCase
{
    public function testDiFactoryDefaultSingleton(): void
    {
        $diFactory = new DiFactory(MyDiFactory::class);

        $this->assertNull($diFactory->isSingleton());
    }

    public function testDiFactoryDefinedSingletonValue(): void
    {
        $diFactory = new DiFactory(MyDiFactory::class, true);

        $this->assertTrue($diFactory->isSingleton());
    }

    #[DataProvider('dataProviderDiFactorySuccess')]
    public function testDiFactorySuccess(array|string $definition, array|string $expectDefinition): void
    {
        self::assertEquals($expectDefinition, (new DiFactory($definition))->getDefinition());
    }

    public static function dataProviderDiFactorySuccess(): Generator
    {
        yield 'none empty string' => ['services.hoho', 'services.hoho'];

        yield 'class string' => [MyDiFactory::class, 'Tests\Attributes\Raw\Fixtures\MyDiFactory'];

        yield 'two elements with class string and method' => [[Foo::class, 'bar'], ['Tests\Attributes\Raw\Fixtures\Foo', 'bar']];

        yield 'two elements with container id and method' => [['services.hoho', 'make'], ['services.hoho', 'make']];

        yield 'many elements in array definition' => [[Foo::class, 'bar', new stdClass()], ['Tests\Attributes\Raw\Fixtures\Foo', 'bar']];

        yield 'string with semicolons for defined factory method' => [Foo::class.'::bar', 'Tests\Attributes\Raw\Fixtures\Foo::bar'];
    }

    #[DataProvider('dataProviderDiFactoryIsFail')]
    public function testDiFactoryIsFail(array|string $definition): void
    {
        $this->expectException(AutowireExceptionInterface::class);

        new DiFactory($definition);
    }

    public static function dataProviderDiFactoryIsFail(): Generator
    {
        yield 'empty string' => [''];

        yield 'one element in array' => [[Foo::class]];

        yield 'first and second element is empty string' => [['', '']];

        yield 'first element ok, second is empty string' => [[Foo::class, '']];

        yield 'first element empty, second ok' => [['', 'make']];

        yield 'array with string key' => [['class' => Foo::class, 'method' => 'bar']];

        yield 'array with none string' => [[new Foo(), 'bar']];
    }
}
