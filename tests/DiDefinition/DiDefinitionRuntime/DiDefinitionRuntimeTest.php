<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionRuntime;

use Kaspi\DiContainer\AttributeReader;
use Kaspi\DiContainer\DiDefinition\DiDefinitionRuntime;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\FreezeTrait;
use Kaspi\DiContainer\Traits\TagsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AttributeReader::class)]
#[CoversClass(DiDefinitionRuntime::class)]
#[CoversClass(TagsTrait::class)]
#[CoversClass(FreezeTrait::class)]
class DiDefinitionRuntimeTest extends TestCase
{
    #[TestWith([null, null])]
    #[TestWith(['Oops!', 'Oops!'])]
    public function testAdditionalMessage(?string $msg, ?string $partOfExpectMessage): void
    {
        $d = new DiDefinitionRuntime('x', $msg);

        self::assertEquals($partOfExpectMessage, $d->getMessage());
    }

    public function testContainerIdentifier(): void
    {
        $d = new DiDefinitionRuntime('service.foo');

        self::assertEquals('service.foo', $d->getIdentifier());
    }

    public function testCannotResolveWithoutDefinition(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('The runtime definition with container identifier \'service.foo\' cannot be resolved.');

        (new DiDefinitionRuntime('service.foo'))
            ->resolve($this->createMock(DiContainerInterface::class))
        ;
    }

    public function testDefinitionNotSetYet(): void
    {
        self::assertNull((new DiDefinitionRuntime('service.foo'))->getDefinition());
    }

    public function testSetAndGetDefinitionAsObject(): void
    {
        $object = (object) ['foo' => 'bar'];

        $d = new DiDefinitionRuntime('service.foo');
        $d->setDefinition($object);

        self::assertSame($object, $d->getDefinition());
        self::assertSame($object, $d->resolve($this->createMock(DiContainerInterface::class)));
    }

    #[TestWith(['foo', null, 'foo'])]
    #[TestWith(['foo', Foo::class, 'Tests\DiDefinition\DiDefinitionRuntime\Foo'])]
    public function testGetDefinitionIdentifier(string $containerIdentifier, ?string $classDefinition, string $expectDefinitionIdentifier): void
    {
        $d = new DiDefinitionRuntime($containerIdentifier, classDefinition: $classDefinition);

        self::assertEquals($expectDefinitionIdentifier, $d->getDefinitionIdentifier());
    }

    public function testIsImplementInterfaceFail(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('You should to be defined a php class through the parameters $containerIdentifierOrClass or $classDefinition');

        (new DiDefinitionRuntime('foo'))->isImplementInterface(FooInterface::class);
    }

    #[TestWith([Foo::class, null, FooInterface::class])]
    #[TestWith(['service.foo', Foo::class, FooInterface::class])]
    public function testIsImplementInterface(string $containerIdentifier, ?string $classDefinition, string $interface): void
    {
        $d = new DiDefinitionRuntime($containerIdentifier, classDefinition: $classDefinition);

        self::assertEquals($containerIdentifier, $d->getIdentifier());
        self::assertTrue($d->isImplementInterface($interface));
    }

    public function testReset(): void
    {
        $d = new DiDefinitionRuntime(Foo::class);
        $d->setDefinition($foo = new Foo());
        $d->setContainer($this->createMock(DiContainerInterface::class));

        self::assertSame($foo, $d->getDefinition());
        self::assertIsArray($d->getTags());

        $d->reset();

        self::assertNull($d->getDefinition());
        // $d->reset() clear container too.
        $this->expectException(DiDefinitionExceptionInterface::class);
        $d->getTags();
    }

    public function testFreeze(): void
    {
        $d = new DiDefinitionRuntime(Foo::class);
        $d->setContainer($this->createMock(DiContainerInterface::class));

        self::assertEmpty($d->getTags());

        $d->bindTag('tags.foo', priority: 10);

        self::assertEquals(['tags.foo' => ['priority' => 10]], $d->getTags());

        $d->freeze();

        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('Cannot call \Kaspi\DiContainer\DiDefinition\DiDefinitionRuntime::bindTag() on a frozen definition.');

        $d->bindTag('tags.baz');
    }
}

interface FooInterface {}
final class Foo implements FooInterface {}
