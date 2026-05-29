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
use Tests\AttributeReader\DiRuntime\Fixtures\Baz;
use Tests\AttributeReader\DiRuntime\Fixtures\Foo;
use Tests\AttributeReader\DiRuntime\Fixtures\FooInvalid;

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
        self::assertEquals('', $attrs[1]->containerIdentifier);
    }
}
