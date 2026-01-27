<?php

declare(strict_types=1);

namespace Tests\Compiler\Compiler;

use Generator;
use Kaspi\DiContainer\Compiler\CompiledEntries;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\ContainerIdentifierExistExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CompiledEntries::class)]
#[CoversClass(CompiledEntry::class)]
class CompiledEntriesTest extends TestCase
{
    public function testEmpty(): void
    {
        $ce = new CompiledEntries();

        self::assertFalse($ce->getCompiledEntries()->valid());
        self::assertFalse($ce->getContainerIdentifierMappedMethodResolve()->valid());
        self::assertFalse($ce->getHasIdentifiers()->valid());
    }

    public function testExistIdentifier(): void
    {
        $this->expectException(ContainerIdentifierExistExceptionInterface::class);

        $ce = new CompiledEntries();

        $ce->setServiceMethod('id1', new CompiledEntry());
        $ce->setServiceMethod('id1', new CompiledEntry());
    }

    public function testExcludeIds(): void
    {
        $ce = new CompiledEntries();

        $ce->setServiceMethod('id1', new CompiledEntry());
        $ce->setServiceMethod('id2', new CompiledEntry());
        $ce->setServiceMethod('id3', new CompiledEntry());

        $ce->addNotFoudContainerId('id2');

        $hasIds = $ce->getHasIdentifiers();

        self::assertEquals('id1', $hasIds->current());
        $hasIds->next();
        self::assertEquals('id3', $hasIds->current());
        $hasIds->next();
        self::assertNull($hasIds->current());
    }

    public function testUniqueServiceMethod(): void
    {
        $ce = new CompiledEntries('resolve_', 'service');

        $ce->setServiceMethod('foo', new CompiledEntry());
        $ce->setServiceMethod('App\Service\Foo', new CompiledEntry());
        $ce->setServiceMethod('App\Validate\Foo', new CompiledEntry());

        $idMapMethod = $ce->getContainerIdentifierMappedMethodResolve();

        self::assertEquals('resolve_foo', $idMapMethod->current()['serviceMethod']);
        $idMapMethod->next();
        self::assertEquals('resolve_foo1', $idMapMethod->current()['serviceMethod']);
        $idMapMethod->next();
        self::assertEquals('resolve_foo2', $idMapMethod->current()['serviceMethod']);
        $idMapMethod->next();
        self::assertNull($idMapMethod->current());
    }

    #[DataProvider('dataProviderSuccess')]
    public function testConvertContainerIdentifierToMethodNameSuccess($prefix, $defaultName, $id, $expectServiceMethod): void
    {
        $ce = new CompiledEntries($prefix, $defaultName);
        $ce->setServiceMethod($id, new CompiledEntry());

        self::assertEquals($expectServiceMethod, $ce->getContainerIdentifierMappedMethodResolve()->current()['serviceMethod']);
    }

    public static function dataProviderSuccess(): Generator
    {
        yield 'fully qualified class name' => [
            'resolve_',
            'service',
            self::class,
            'resolve_compiled_entries_test',
        ];

        yield 'string ascii with doted' => [
            'resolve_',
            'service',
            'services.foo',
            'resolve_services_foo',
        ];

        yield 'string with symbols not valid for method name' => [
            'resolve_',
            'service',
            '111-222',
            'resolve_service',
        ];

        yield 'string with start symbols not valid for method name' => [
            'resolve_',
            'service',
            ',.~method',
            'resolve_method',
        ];

        yield 'string with some symbols not valid for method name' => [
            'resolve_',
            'service',
            'температура   -20',
            'resolve_температура____20',
        ];

        yield 'empty string' => [
            'resolve_',
            'service',
            '',
            'resolve_service',
        ];

        yield 'spaces string' => [
            'resolve_',
            'service',
            '  ',
            'resolve_service',
        ];
    }

    public function testGetCompiledEntries(): void
    {
        $ce = new CompiledEntries('resolve_');

        $ce->setServiceMethod('id1', new CompiledEntry(isSingleton: true));

        $compiledEntries = $ce->getCompiledEntries();

        $item = $compiledEntries->current();
        self::assertEquals('resolve_id1', $item['serviceMethod']);
        self::assertEquals('id1', $item['id']);
        self::assertEquals(new CompiledEntry(isSingleton: true), $item['entry']);

        $compiledEntries->next();
        self::assertNull($compiledEntries->current());
    }

    public function testReset(): void
    {
        $ce = new CompiledEntries();

        $ce->setServiceMethod('id1', new CompiledEntry(isSingleton: true));

        self::assertTrue($ce->getHasIdentifiers()->valid());
        self::assertTrue($ce->getContainerIdentifierMappedMethodResolve()->valid());
        self::assertTrue($ce->getCompiledEntries()->valid());

        $ce->reset();

        self::assertFalse($ce->getHasIdentifiers()->valid());
        self::assertFalse($ce->getContainerIdentifierMappedMethodResolve()->valid());
        self::assertFalse($ce->getCompiledEntries()->valid());
    }
}
