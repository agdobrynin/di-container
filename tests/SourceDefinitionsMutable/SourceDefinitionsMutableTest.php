<?php

declare(strict_types=1);

namespace Tests\SourceDefinitionsMutable;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\SourceDefinitionsMutableExceptionInterface;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\DeferredSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_keys;

/**
 * @internal
 */
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(DeferredSourceDefinitionsMutable::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionValue::class)]
class SourceDefinitionsMutableTest extends TestCase
{
    #[DataProvider('provideIterableType')]
    public function testConstructorIterableType(iterable $src, string $id): void
    {
        self::assertTrue(isset((new DeferredSourceDefinitionsMutable(static fn () => $src))[$id]));
    }

    public static function provideIterableType(): Generator
    {
        yield 'array' => [
            'src' => ['service.foo' => 'foo value'],
            'id' => 'service.foo',
        ];

        yield 'Generator' => [
            'src' => (static fn (): Generator => yield from ['service.foo' => 'foo value'])(),
            'id' => 'service.foo',
        ];

        yield 'Other SourceDefinitionsMutable class' => [
            'src' => new DeferredSourceDefinitionsMutable(static fn () => ['service.foo' => 'foo value']),
            'id' => 'service.foo',
        ];

        yield 'Object implement DiDefinitionIdentifierInterface' => [
            'src' => new DeferredSourceDefinitionsMutable(static fn () => [new DiDefinitionAutowire(self::class)]),
            'id' => self::class,
        ];
    }

    public function testUseIssetFnOnNullValue(): void
    {
        $s = new DeferredSourceDefinitionsMutable(static fn () => [
            'service.null' => null,
        ]);

        self::assertTrue(isset($s['service.null']));
        self::assertFalse(isset($s['service.not_exist']));
    }

    public function testNoneValidKeyInDefinitionsThroughConstructor(): void
    {
        $this->expectException(ContainerIdentifierExceptionInterface::class);
        $this->expectExceptionMessage('Definition identifier must be a non-empty string');

        $s = new DeferredSourceDefinitionsMutable(static fn () => ['' => new DiDefinitionValue('ooo')]);
        $s->getIterator()->valid();
    }

    public function testGetUndefinedKey(): void
    {
        $this->expectException(SourceDefinitionsMutableExceptionInterface::class);
        $this->expectExceptionMessage('Unregistered the container identifier "service.foo" in the source.');

        $s = new DeferredSourceDefinitionsMutable(static fn () => ['service.bar' => 'Lorem ipsum']);
        $s['service.foo'];
    }

    public function testUnsetNotAvailable(): void
    {
        $this->expectException(SourceDefinitionsMutableExceptionInterface::class);
        $this->expectExceptionMessage('Definitions in the source are non-removable. Operation using the container identifier "service.bar".');

        $s = new DeferredSourceDefinitionsMutable(static fn () => ['service.bar' => 'Lorem ipsum']);
        unset($s['service.bar']);
    }

    public function testSetSuccess(): void
    {
        $s = new DeferredSourceDefinitionsMutable(static fn () => ['service.bar' => 'Service bar']);
        $s['service.baz'] = 'Service baz';
        $s[] = new DiDefinitionAutowire(self::class);

        self::assertEquals(
            [
                'service.bar' => new DiDefinitionValue('Service bar'),
                'service.baz' => new DiDefinitionValue('Service baz'),
                self::class => new DiDefinitionAutowire(self::class),
            ],
            [...$s->getIterator()]
        );
    }

    public function testSetFail(): void
    {
        $this->expectException(ContainerAlreadyRegisteredExceptionInterface::class);
        $this->expectExceptionMessage('The container identifier "service.bar" already registered in the source.');

        $s = new DeferredSourceDefinitionsMutable(static fn () => ['service.bar' => 'Service bar']);
        $s['service.bar'] = 'Other value';
    }

    #[DataProvider('dataProviderWrongKeyForSetter')]
    public function testNoneStringKeyForSetter(mixed $offset): void
    {
        $this->expectException(ContainerIdentifierExceptionInterface::class);
        $this->expectExceptionMessage('Definition identifier must be a non-empty string');

        $s = new DeferredSourceDefinitionsMutable(static fn () => []);
        $s[$offset] = 'Service value';
    }

    public static function dataProviderWrongKeyForSetter(): Generator
    {
        yield 'object' => [new stdClass()];

        yield 'array' => [[]];

        yield 'bool' => [true];

        yield 'empty string' => [''];
    }

    public function testNoneStringKeyForGetter(): void
    {
        $this->expectException(SourceDefinitionsMutableExceptionInterface::class);
        $this->expectExceptionMessage('Unsupported identifier type "stdClass"');

        (new DeferredSourceDefinitionsMutable(static fn () => []))[new stdClass()];
    }

    public function testNoneStringKeyForExister(): void
    {
        $s = new DeferredSourceDefinitionsMutable(static fn () => []);

        self::assertFalse(isset($s[new stdClass()]));
    }

    public function testNoneStringKeyForUnset(): void
    {
        $this->expectException(SourceDefinitionsMutableExceptionInterface::class);
        $this->expectExceptionMessage('Definitions in the source are non-removable');

        $s = new DeferredSourceDefinitionsMutable(static fn () => []);
        unset($s[new stdClass()]);
    }

    public function testUnset(): void
    {
        $this->expectException(SourceDefinitionsMutableExceptionInterface::class);
        $this->expectExceptionMessage('Definitions in the source are non-removable');

        $src = static fn (): iterable => ['service.bar' => 'Service bar'];

        $s = new DeferredSourceDefinitionsMutable($src);
        unset($s['service.bar']);
    }

    public function testRemovedDefinitionIds(): void
    {
        $s = new DeferredSourceDefinitionsMutable(
            static fn () => [
                'service.bar' => 'Service bar',
                'service.baz' => 'Service baz',
                'service.foo' => 'Service foo',
            ],
            static fn () => [
                'service.foo' => true,
            ]
        );

        self::assertTrue($s->isRemovedDefinition('service.foo'));
        self::assertFalse($s->isRemovedDefinition('service.bar'));

        // exclude id 'service.foo'
        self::assertSame(['service.bar', 'service.baz'], array_keys([...$s->getIterator()]));

        // set service id and remove if exist in `removedDefinitionIds`
        $s['service.foo'] = 'Service foo';
        self::assertFalse($s->isRemovedDefinition('service.foo'));
    }

    public function testInitRemovedDefinitionIdsAfterInitDefinitions(): void
    {
        $s = new DeferredSourceDefinitionsMutable(
            static fn () => [
                'service.bar' => 'Service bar',
            ],
            static fn () => [
                'service.foo' => true,
            ]
        );

        self::assertTrue($s->isRemovedDefinition('service.foo'));
    }
}
