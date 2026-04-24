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
        $this->expectExceptionMessage('The "runtime definition" cannot be resolved');

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
}
