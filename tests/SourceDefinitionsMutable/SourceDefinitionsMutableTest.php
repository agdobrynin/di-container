<?php

declare(strict_types=1);

namespace Tests\SourceDefinitionsMutable;

use Generator;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionRuntime;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\DeferredSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_keys;

/**
 * @internal
 */
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(DeferredSourceDefinitionsMutable::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionRuntime::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(Helper::class)]
#[CoversClass(ContainerAlreadyRegisteredException::class)]
class SourceDefinitionsMutableTest extends TestCase
{
    #[DataProvider('provideIterableType')]
    public function testConstructorIterableType(iterable $src, string $id): void
    {
        self::assertTrue((new DeferredSourceDefinitionsMutable(static fn () => $src))->has($id));
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

        self::assertTrue($s->has('service.null'));
        self::assertFalse($s->has('service.not_exist'));
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
        $s = new DeferredSourceDefinitionsMutable(static fn () => ['service.bar' => 'Lorem ipsum']);

        self::assertNull($s->get('service.foo'));
    }

    public function testSetSuccess(): void
    {
        $s = new DeferredSourceDefinitionsMutable(static fn () => ['service.bar' => 'Service bar']);
        $s->set('service.baz', 'Service baz');
        $s->set(0, new DiDefinitionAutowire(self::class));

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
        $this->expectExceptionMessage('The container identifier \'service.bar\' already registered in the source.');

        $s = new DeferredSourceDefinitionsMutable(static fn () => ['service.bar' => 'Service bar']);
        $s->set('service.bar', 'Other value');
    }

    #[DataProvider('dataProviderWrongKeyForSetter')]
    public function testNoneStringKeyForSetter(int|string $offset, mixed $value): void
    {
        $this->expectException(ContainerIdentifierExceptionInterface::class);
        $this->expectExceptionMessage('Definition identifier must be a non-empty string');

        $s = new DeferredSourceDefinitionsMutable(static fn () => []);
        $s->set($offset, $value);
    }

    public static function dataProviderWrongKeyForSetter(): Generator
    {
        yield 'empty key' => ['', 'foo'];

        yield 'number and value none implement DiDefinitionIdentifierInterface' => [10, 'foo'];
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
        $s->set('service.foo', 'Service foo');
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

    public function testReplaceRuntimeDefinitionSuccess(): void
    {
        $s = new DeferredSourceDefinitionsMutable(
            static fn () => yield new DiDefinitionRuntime('service.foo')
        );

        $s->set('service.foo', $instance = (object) ['bar' => 'Service bar']);

        self::assertSame($instance, $s->get('service.foo')->getDefinition());
    }

    public function testReplaceRuntimeDefinitionFail(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('The runtime definition with the identifier \'service.foo\' must be specified as an object.');

        $s = new DeferredSourceDefinitionsMutable(
            static fn () => yield new DiDefinitionRuntime('service.foo')
        );

        $s->set('service.foo', ['bar' => 'Service bar']);
    }

    public function testUseStaticFabricAsSource(): void
    {
        $s = new DeferredSourceDefinitionsMutable(
            [SourceDefinitions::class, 'src'],
            '\Tests\SourceDefinitionsMutable\SourceDefinitions::removed'
        );

        self::assertFalse($s->has('baz'));
        self::assertTrue($s->isRemovedDefinition('baz'));

        self::assertTrue($s->has('foo'));
        self::assertTrue($s->has('qux'));
    }

    public function testHasEmptyString(): void
    {
        $s = new DeferredSourceDefinitionsMutable(static fn () => []);

        self::assertFalse($s->has(''));
    }
}

final class SourceDefinitions
{
    public static function src(): Generator
    {
        yield 'foo' => 'bar';

        yield 'baz' => 'fuz';

        yield 'qux' => 'quux';
    }

    public static function removed(): Generator
    {
        yield 'baz' => true;
    }
}
