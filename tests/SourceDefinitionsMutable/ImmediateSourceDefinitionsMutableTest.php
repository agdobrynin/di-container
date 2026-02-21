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
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_keys;

/**
 * @internal
 */
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionValue::class)]
class ImmediateSourceDefinitionsMutableTest extends TestCase
{
    #[DataProvider('provideIterableType')]
    public function testConstructorIterableType(iterable $src, string $id): void
    {
        self::assertTrue(isset((new ImmediateSourceDefinitionsMutable($src))[$id]));
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
            'src' => new ImmediateSourceDefinitionsMutable(['service.foo' => 'foo value']),
            'id' => 'service.foo',
        ];

        yield 'Object implement DiDefinitionIdentifierInterface' => [
            'src' => new ImmediateSourceDefinitionsMutable([new DiDefinitionAutowire(self::class)]),
            'id' => self::class,
        ];
    }

    public function testUseIssetFnOnNullValue(): void
    {
        $s = new ImmediateSourceDefinitionsMutable([
            'service.null' => null,
        ]);

        self::assertTrue(isset($s['service.null']));
        self::assertFalse(isset($s['service.not_exist']));
    }

    public function testGetUndefinedKey(): void
    {
        $this->expectException(SourceDefinitionsMutableExceptionInterface::class);
        $this->expectExceptionMessage('Unregistered the container identifier "service.foo" in the source.');

        $s = new ImmediateSourceDefinitionsMutable(['service.bar' => 'Lorem ipsum']);
        $s['service.foo'];
    }

    public function testUnsetNotAvailable(): void
    {
        $this->expectException(SourceDefinitionsMutableExceptionInterface::class);
        $this->expectExceptionMessage('Definitions in the source are non-removable. Operation using the container identifier "service.bar".');

        $s = new ImmediateSourceDefinitionsMutable(['service.bar' => 'Lorem ipsum']);
        unset($s['service.bar']);
    }

    public function testSetSuccess(): void
    {
        $s = new ImmediateSourceDefinitionsMutable(['service.bar' => 'Service bar']);
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

        $s = new ImmediateSourceDefinitionsMutable(['service.bar' => 'Service bar']);
        $s['service.bar'] = 'Other value';
    }

    public function testNoneValidKeyInDefinitionsThroughConstructor(): void
    {
        $this->expectException(ContainerIdentifierExceptionInterface::class);
        $this->expectExceptionMessage('Definition identifier must be a non-empty string');

        new ImmediateSourceDefinitionsMutable(['' => 'Service bar']);
    }

    public function testKeyExistThroughConstructor(): void
    {
        $this->expectException(ContainerAlreadyRegisteredExceptionInterface::class);
        $this->expectExceptionMessage('The container identifier "service.foo" already registered in the source.');

        $defs = static function (): Generator {
            yield 'service.foo' => 'foo value';

            yield 'service.foo' => 'duplicate foo value';
        };

        new ImmediateSourceDefinitionsMutable($defs());
    }

    #[DataProvider('dataProviderWrongKeyForSetter')]
    public function testNoneStringKeyForSetter(mixed $offset): void
    {
        $this->expectException(ContainerIdentifierExceptionInterface::class);
        $this->expectExceptionMessage('Definition identifier must be a non-empty string');

        $s = new ImmediateSourceDefinitionsMutable([]);
        $s[$offset] = 'service value';
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

        $s = new ImmediateSourceDefinitionsMutable([]);
        $s[new stdClass()];
    }

    public function testNoneStringKeyForExister(): void
    {
        $s = new ImmediateSourceDefinitionsMutable([]);

        self::assertFalse(isset($s[new stdClass()]));
    }

    public function testNoneStringKeyForUnset(): void
    {
        $this->expectException(SourceDefinitionsMutableExceptionInterface::class);
        $this->expectExceptionMessage('Definitions in the source are non-removable');

        $s = new ImmediateSourceDefinitionsMutable([]);
        unset($s[new stdClass()]);
    }

    public function testUnset(): void
    {
        $this->expectException(SourceDefinitionsMutableExceptionInterface::class);
        $this->expectExceptionMessage('Definitions in the source are non-removable');

        $s = new ImmediateSourceDefinitionsMutable(['service.bar' => 'Service bar']);
        unset($s['service.bar']);
    }

    public function testRemovedDefinitionIds(): void
    {
        $s = new ImmediateSourceDefinitionsMutable(
            [
                'service.bar' => 'Service bar',
                'service.baz' => 'Service baz',
                'service.foo' => 'Service foo',
            ],
            [
                'service.foo' => true,
            ]
        );

        // exclude id 'service.foo'
        self::assertSame(['service.bar', 'service.baz'], array_keys([...$s->getIterator()]));

        self::assertTrue($s->isRemovedDefinition('service.foo'));
        self::assertFalse($s->isRemovedDefinition('service.bar'));

        // set service id and remove if exist in `removedDefinitionIds`
        $s['service.foo'] = 'Service foo';
        self::assertFalse($s->isRemovedDefinition('service.foo'));
    }
}
