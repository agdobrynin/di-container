<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionRuntime;

use Kaspi\DiContainer\DiDefinition\DiDefinitionRuntime;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DiDefinitionRuntime::class)]
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
        $this->expectExceptionMessage('You should to be defined a php class through the parameters $containerIdentifier or $classDefinition');

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
}

interface FooInterface {}
final class Foo implements FooInterface {}
