<?php

declare(strict_types=1);

namespace Tests\AttributeReader\DiRuntime;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\AttributeReader\DiRuntime\Fixtures\Bar;
use Tests\AttributeReader\DiRuntime\Fixtures\Bat;
use Tests\AttributeReader\DiRuntime\Fixtures\BatFailResetter;
use Tests\AttributeReader\DiRuntime\Fixtures\Baz;
use Tests\AttributeReader\DiRuntime\Fixtures\Foo;
use Tests\AttributeReader\DiRuntime\Fixtures\FooInvalid;
use Tests\AttributeReader\DiRuntime\Fixtures\Qux;
use Tests\AttributeReader\DiRuntime\Fixtures\QuxResetter;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiRuntime::class)]
class DiRuntimeReaderTest extends TestCase
{
    #[TestWith([Bar::class, '/The attributes .+DiRuntime and .+Autowire cannot be declared together/'])]
    #[TestWith([Baz::class, '/The attributes .+DiRuntime and .+DiFactory cannot be declared together/'])]
    #[TestWith([FooInvalid::class, '/Container identifier.+".+FooInvalid" already defined via previous php attribute/'])]
    public function testIntersectAttrs(string $class, string $expectMessageMatch): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches($expectMessageMatch);

        [...AttributeReader::getDiRuntimeAttribute(new ReflectionClass($class))];
    }

    public function testReadAllDiRuntimeAttrs(): void
    {
        /** @var list<DiRuntime> $attrs */
        $attrs = [...AttributeReader::getDiRuntimeAttribute(new ReflectionClass(Foo::class))];

        self::assertCount(2, $attrs);

        self::assertEquals('foo', $attrs[0]->containerIdentifier);
        self::assertFalse($attrs[0]->getResetter());

        self::assertEquals('', $attrs[1]->containerIdentifier);
        self::assertFalse($attrs[1]->getResetter());
    }

    public function testDiRuntimeWithFailResetter(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Argument.+\(\$resetter\) must be of type callable\|string\|false, array given/');

        [...AttributeReader::getDiRuntimeAttribute(new ReflectionClass(BatFailResetter::class))];
    }

    public function testDiRuntimeResetterAsMethod(): void
    {
        $runtime = AttributeReader::getDiRuntimeAttribute(new ReflectionClass(Bat::class))->current();

        self::assertEquals('doReset', $runtime->getResetter());
    }

    public function testDiRuntimeResetterAsCallable(): void
    {
        $runtime = AttributeReader::getDiRuntimeAttribute(new ReflectionClass(Qux::class))->current();

        self::assertIsCallable($resetter = $runtime->getResetter());
        self::assertEquals([QuxResetter::class, 'doReset'], $resetter);
    }
}
