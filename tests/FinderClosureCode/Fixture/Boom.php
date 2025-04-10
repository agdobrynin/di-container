<?php

declare(strict_types=1);

namespace Tests\FinderClosureCode\Fixture;

use Baz\Bar\Foo;
use Faz\Bar\Foo as Faz;
use Foo as FooRoot;

final class Boom
{
    public static function boom(): callable
    {
        return static function (): Foo {
            FooRoot::setup(params: ['foo' => __NAMESPACE__]);

            return (new Foo(
                'Something',
                params: [
                    'foo' => 'bar',
                    'bar' => 'baz'
                ]
            ))
                ->setup(
                    new Faz(
                        str: 'yes',
                        foo: new Foo(
                            'otherSomething',
                            params: [
                                'ozz' => true,
                            ]
                        )
                    )
                );
        };
    }
}
