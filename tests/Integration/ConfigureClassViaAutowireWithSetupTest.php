<?php

declare(strict_types=1);

namespace Tests\Integration;

use Kaspi\DiContainer\DiContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\Integration\Fixtures\ConfigureClassViaAutowireWithSetup\Bar;
use Tests\Integration\Fixtures\ConfigureClassViaAutowireWithSetup\Baz;
use Tests\Integration\Fixtures\ConfigureClassViaAutowireWithSetup\Foo;
use Tests\Integration\Fixtures\ConfigureClassViaAutowireWithSetup\Qux;

/**
 * @internal
 */
#[CoversNothing]
class ConfigureClassViaAutowireWithSetupTest extends TestCase
{
    public function testAutoconfigViaAutowireWithSetup(): void
    {
        $container = (new DiContainerBuilder())
            ->import('Tests\\', __DIR__.'/Fixtures/ConfigureClassViaAutowireWithSetup')
            ->build()
        ;

        self::assertEquals('Lorem ipsum for id "Bar"', $container->get(Bar::class)->getVal());
        self::assertEquals('Lorem ipsum for id "services.bar"', $container->get('services.bar')->getVal());

        $foo = $container->get(Foo::class);

        self::assertCount(2, $foo->taggedOne);
        self::assertEquals('Lorem ipsum for id "Bar"', $foo->taggedOne->get(Bar::class)->getVal());
        self::assertInstanceOf(Baz::class, $foo->taggedOne->get(Baz::class));

        self::assertCount(2, $foo->taggedTwo);
        self::assertEquals('Lorem ipsum for id "services.bar"', $foo->taggedTwo->get('services.bar')->getVal());
        self::assertInstanceOf(Qux::class, $foo->taggedTwo->get(Qux::class));
    }
}
