<?php

declare(strict_types=1);

namespace Tests\Tag;

use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;

use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diTaggedAs;
use function Kaspi\DiContainer\diValue;

/**
 * @internal
 *
 * @covers \Kaspi\DiContainer\Attributes\TaggedAs
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diTaggedAs
 * @covers \Kaspi\DiContainer\diValue
 * @covers \Kaspi\DiContainer\Helper
 * @covers \Kaspi\DiContainer\LazyDefinitionIterator
 */
class TaggedAsUseKeysTest extends TestCase
{
    public function testTaggedAsNotLazyUseKeysTrueForPhpDefinition(): void
    {
        $container = (new DiContainerFactory())->make([
            'emails.admin' => diValue('admin@company.com')
                ->bindTag('site.emails', priority: 1),
            'emails.manager' => diValue('manager@company.com')
                ->bindTag('site.emails', priority: 2),
            'emails.user' => diValue('user@company.com'),
            'func.emails' => diCallable(static fn (array $emails) => $emails)
                ->bindArguments(emails: diTaggedAs('site.emails', isLazy: false)),
        ]);

        $this->assertEquals(
            ['emails.manager' => 'manager@company.com', 'emails.admin' => 'admin@company.com'],
            $container->get('func.emails')
        );
    }

    public function testTaggedAsNotLazyUseKeysTrueForPhpAttribute(): void
    {
        $container = (new DiContainerFactory())->make([
            'emails.admin' => diValue('admin@company.com')
                ->bindTag('site.emails', priority: 1),
            'emails.manager' => diValue('manager@company.com')
                ->bindTag('site.emails', priority: 2),
            'emails.user' => diValue('user@company.com'),
            'func.emails' => diCallable(static fn (
                #[TaggedAs('site.emails', isLazy: false, useKeys: true)]
                array $emails
            ) => $emails),
        ]);

        $this->assertEquals(
            ['emails.manager' => 'manager@company.com', 'emails.admin' => 'admin@company.com'],
            $container->get('func.emails')
        );
    }

    public function testTaggedAsNotLazyUseKeysFalseForPhpDefinition(): void
    {
        $container = (new DiContainerFactory())->make([
            'emails.admin' => diValue('admin@company.com')
                ->bindTag('site.emails', priority: 1),
            'emails.manager' => diValue('manager@company.com')
                ->bindTag('site.emails', priority: 2),
            'emails.user' => diValue('user@company.com'),
            'func.emails' => diCallable(static fn (array $emails) => $emails)
                ->bindArguments(emails: diTaggedAs('site.emails', isLazy: false, useKeys: false)),
        ]);

        $this->assertEquals(
            ['manager@company.com', 'admin@company.com'],
            $container->get('func.emails')
        );
    }

    public function testTaggedAsNotLazyUseKeysFalseForPhpAttribute(): void
    {
        $container = (new DiContainerFactory())->make([
            'emails.admin' => diValue('admin@company.com')
                ->bindTag('site.emails', priority: 1),
            'emails.manager' => diValue('manager@company.com')
                ->bindTag('site.emails', priority: 2),
            'emails.user' => diValue('user@company.com'),
            'func.emails' => diCallable(static fn (
                #[TaggedAs('site.emails', isLazy: false, useKeys: false)]
                array $emails
            ) => $emails),
        ]);

        $this->assertEquals(
            ['manager@company.com', 'admin@company.com'],
            $container->get('func.emails')
        );
    }

    public function testTaggedAsLazyUseKeysTrueForPhpDefinition(): void
    {
        $container = (new DiContainerFactory())->make([
            'emails.admin' => diValue('admin@company.com')
                ->bindTag('site.emails', priority: 1),
            'emails.manager' => diValue('manager@company.com')
                ->bindTag('site.emails', priority: 2),
            'emails.user' => diValue('user@company.com'),
            'func.emails' => diCallable(static fn (iterable $emails) => $emails)
                ->bindArguments(emails: diTaggedAs('site.emails')),
        ]);

        $res = $container->get('func.emails');

        $this->assertIsIterable($res);
        $this->assertEquals('emails.manager', $res->key());
        $res->next();
        $this->assertEquals('emails.admin', $res->key());
        $res->next();
        $this->assertFalse($res->valid());
    }

    public function testTaggedAsLazyUseKeysTrueForPhpAttribute(): void
    {
        $container = (new DiContainerFactory())->make([
            'emails.admin' => diValue('admin@company.com')
                ->bindTag('site.emails', priority: 1),
            'emails.manager' => diValue('manager@company.com')
                ->bindTag('site.emails', priority: 2),
            'emails.user' => diValue('user@company.com'),
            'func.emails' => diCallable(static fn (
                #[TaggedAs('site.emails', useKeys: true)]
                iterable $emails
            ) => $emails),
        ]);

        $res = $container->get('func.emails');

        $this->assertIsIterable($res);
        $this->assertEquals('emails.manager', $res->key());
        $res->next();
        $this->assertEquals('emails.admin', $res->key());
        $res->next();
        $this->assertFalse($res->valid());
    }

    public function testTaggedAsLazyUseKeysFalseForPhpDefinition(): void
    {
        $container = (new DiContainerFactory())->make([
            'emails.admin' => diValue('admin@company.com')
                ->bindTag('site.emails', priority: 1),
            'emails.manager' => diValue('manager@company.com')
                ->bindTag('site.emails', priority: 2),
            'emails.user' => diValue('user@company.com'),
            'func.emails' => diCallable(static fn (iterable $emails) => $emails)
                ->bindArguments(emails: diTaggedAs('site.emails', useKeys: false)),
        ]);

        $res = $container->get('func.emails');

        $this->assertIsIterable($res);
        $this->assertEquals(0, $res->key());
        $res->next();
        $this->assertEquals(1, $res->key());
        $res->next();
        $this->assertFalse($res->valid());
    }

    public function testTaggedAsLazyUseKeysFalseForPhpAttribute(): void
    {
        $container = (new DiContainerFactory())->make([
            'emails.admin' => diValue('admin@company.com')
                ->bindTag('site.emails', priority: 1),
            'emails.manager' => diValue('manager@company.com')
                ->bindTag('site.emails', priority: 2),
            'emails.user' => diValue('user@company.com'),
            'func.emails' => diCallable(static fn (
                #[TaggedAs('site.emails', useKeys: false)]
                iterable $emails
            ) => $emails),
        ]);

        $res = $container->get('func.emails');

        $this->assertIsIterable($res);
        $this->assertEquals(0, $res->key());
        $res->next();
        $this->assertEquals(1, $res->key());
        $res->next();
        $this->assertFalse($res->valid());
    }
}
