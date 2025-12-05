<?php

declare(strict_types=1);

namespace Tests\Attributes\Raw;

use Generator;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Attributes\Raw\Fixtures\Bar;
use Tests\Attributes\Raw\Fixtures\Foo;

/**
 * @internal
 */
#[CoversClass(InjectByCallable::class)]
class InjectByCallableTest extends TestCase
{
    #[DataProvider('successIdsDataProvider')]
    public function testSuccess(callable $def): void
    {
        $this->assertEquals($def, (new InjectByCallable($def))->getCallable());
    }

    public static function successIdsDataProvider(): Generator
    {
        yield 'function' => ['log'];

        yield 'static method as string with full namespace' => ['Tests\Attributes\Raw\Fixtures\Foo::bar'];

        yield 'static method as string with safe declaration class' => [Foo::class.'::bar'];

        yield 'static method as array' => [[Foo::class, 'bar']];

        yield 'with class as objet and method' => [[new Bar('secure_string'), 'baz']];

        yield 'as closure' => [static function () { return new Bar('secure_string'); }];

        yield 'as first callable class' => [log(...)];
    }
}
