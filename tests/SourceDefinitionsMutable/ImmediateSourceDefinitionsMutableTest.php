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
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_keys;

/**
 * @internal
 */
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(DiDefinitionValue::class)]
#[CoversClass(DiDefinitionRuntime::class)]
#[CoversClass(Helper::class)]
#[CoversClass(ContainerAlreadyRegisteredException::class)]
class ImmediateSourceDefinitionsMutableTest extends TestCase
{
    #[DataProvider('provideIterableType')]
    public function testConstructorIterableType(iterable $src, string $id): void
    {
        self::assertTrue((new ImmediateSourceDefinitionsMutable($src))->has($id));
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

        self::assertTrue($s->has('service.null'));
        self::assertFalse($s->has('service.not_exist'));
    }

    public function testGetUndefinedKey(): void
    {
        $s = new ImmediateSourceDefinitionsMutable(['service.bar' => 'Lorem ipsum']);

        self::assertNull($s->get('service.foo'));
    }

    public function testSetSuccess(): void
    {
        $s = new ImmediateSourceDefinitionsMutable(['service.bar' => 'Service bar']);
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

        $s = new ImmediateSourceDefinitionsMutable(['service.bar' => 'Service bar']);
        $s->set('service.bar', 'Other value');
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
        $this->expectExceptionMessage('The container identifier \'service.foo\' already registered in the source.');

        $defs = static function (): Generator {
            yield 'service.foo' => 'foo value';

            yield 'service.foo' => 'duplicate foo value';
        };

        new ImmediateSourceDefinitionsMutable($defs());
    }

    public function testEmptyStringKeyForSetter(): void
    {
        $this->expectException(ContainerIdentifierExceptionInterface::class);
        $this->expectExceptionMessage('Definition identifier must be a non-empty string');

        $s = new ImmediateSourceDefinitionsMutable([]);
        $s->set('', 'service value');
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
        $s->set('service.foo', 'Service foo');
        self::assertFalse($s->isRemovedDefinition('service.foo'));
    }

    public function testReplaceRuntimeDefinitionSuccess(): void
    {
        $s = new ImmediateSourceDefinitionsMutable([
            new DiDefinitionRuntime('service.foo'),
        ]);

        $s->set('service.foo', $instance = (object) ['bar' => 'Service bar']);

        self::assertSame($instance, $s->get('service.foo')->getDefinition());
    }

    public function testReplaceRuntimeDefinitionFail(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('The runtime definition with the identifier \'service.foo\' must be specified as an object.');

        $s = new ImmediateSourceDefinitionsMutable([
            new DiDefinitionRuntime('service.foo'),
        ]);

        $s->set('service.foo', ['bar' => 'Service bar']);
    }

    public function testHasEmptyString(): void
    {
        $s = new ImmediateSourceDefinitionsMutable([]);

        self::assertFalse($s->has(''));
    }
}
