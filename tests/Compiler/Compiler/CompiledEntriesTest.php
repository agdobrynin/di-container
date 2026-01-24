<?php

declare(strict_types=1);

namespace Tests\Compiler\Compiler;

use Kaspi\DiContainer\Compiler\CompiledEntries;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use PHPUnit\Framework\Attributes\CoversClass;
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

    public function testExcludeIds(): void
    {
        $ce = new CompiledEntries();

        $ce->setServiceMethod('method1', 'id1', new CompiledEntry());
        $ce->setServiceMethod('method2', 'id2', new CompiledEntry());
        $ce->setServiceMethod('method3', 'id3', new CompiledEntry());

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
        $ce = new CompiledEntries();

        $ce->setServiceMethod('method', 'id1', new CompiledEntry());
        $ce->setServiceMethod('method', 'id2', new CompiledEntry());
        $ce->setServiceMethod('method', 'id3', new CompiledEntry());

        $idMapMethod = $ce->getContainerIdentifierMappedMethodResolve();

        self::assertEquals('method', $idMapMethod->current()['serviceMethod']);
        $idMapMethod->next();
        self::assertEquals('method1', $idMapMethod->current()['serviceMethod']);
        $idMapMethod->next();
        self::assertEquals('method2', $idMapMethod->current()['serviceMethod']);
        $idMapMethod->next();
        self::assertNull($idMapMethod->current());
    }

    public function testGetCompiledEntries(): void
    {
        $ce = new CompiledEntries();

        $ce->setServiceMethod('method', 'id1', new CompiledEntry(isSingleton: true));

        $compiledEntries = $ce->getCompiledEntries();

        $item = $compiledEntries->current();
        self::assertEquals('method', $item['serviceMethod']);
        self::assertEquals('id1', $item['id']);
        self::assertEquals(new CompiledEntry(isSingleton: true), $item['entry']);

        $compiledEntries->next();
        self::assertNull($compiledEntries->current());
    }

    public function testReset(): void
    {
        $ce = new CompiledEntries();

        $ce->setServiceMethod('method', 'id1', new CompiledEntry(isSingleton: true));

        self::assertTrue($ce->getHasIdentifiers()->valid());
        self::assertTrue($ce->getContainerIdentifierMappedMethodResolve()->valid());
        self::assertTrue($ce->getCompiledEntries()->valid());

        $ce->reset();

        self::assertFalse($ce->getHasIdentifiers()->valid());
        self::assertFalse($ce->getContainerIdentifierMappedMethodResolve()->valid());
        self::assertFalse($ce->getCompiledEntries()->valid());
    }
}
