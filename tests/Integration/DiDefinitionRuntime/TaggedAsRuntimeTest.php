<?php

declare(strict_types=1);

namespace Tests\Integration\DiDefinitionRuntime;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerNullConfig;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Integration\DiDefinitionRuntime\Fixtures2\Baz;
use Tests\Integration\DiDefinitionRuntime\Fixtures2\Qux;

/**
 * @internal
 */
#[CoversNothing]
class TaggedAsRuntimeTest extends TestCase
{
    public function testTaggedAsRuntimeBindTag(): void
    {
        $c = (new DiContainerBuilder(
            new DiContainerNullConfig()
        ))
            ->load(__DIR__.'/Fixtures2/services.php')
            ->build()
        ;

        $baz = $c->get(Baz::class);

        self::assertEquals('tag_foo', $baz->tagged->key());
        $baz->tagged->next();
        self::assertEquals('Tests\Integration\DiDefinitionRuntime\Fixtures2\Bar', $baz->tagged->key());
        $baz->tagged->next();
        self::assertFalse($baz->tagged->valid());

        $qux = $c->get(Qux::class);
        self::assertEquals('service.foo', $qux->tagged->key());
        $qux->tagged->next();
        self::assertFalse($qux->tagged->valid());

        $baz->tagged->rewind();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('The runtime definition with container identifier \'service.foo\' cannot be resolved.');

        $baz->tagged->current();
    }

    public function testTaggedAsRuntimeTagViaAttributes(): void
    {
        $c = (new DiContainerBuilder())
            ->import('Tests\\', __DIR__.'/Fixtures2')
            ->build()
        ;

        $baz = $c->get(Baz::class);

        self::assertEquals('tag_foo', $baz->tagged->key());
        $baz->tagged->next();
        self::assertEquals('Tests\Integration\DiDefinitionRuntime\Fixtures2\Bar', $baz->tagged->key());
        $baz->tagged->next();
        self::assertFalse($baz->tagged->valid());

        $qux = $c->get(Qux::class);
        self::assertEquals('service.foo', $qux->tagged->key());
        $qux->tagged->next();
        self::assertFalse($qux->tagged->valid());

        $qux->tagged->rewind();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('The runtime definition with container identifier \'service.foo\' cannot be resolved.');

        $qux->tagged->current();
    }
}
