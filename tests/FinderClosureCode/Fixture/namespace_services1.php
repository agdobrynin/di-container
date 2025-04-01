<?php

declare(strict_types=1);

namespace Baz;

use ArrayIterator as ai;

class Foo {
    public function __construct(string $file, string $namespace) {}

    public function bar(ai $bar): void {}
}

namespace Foo\Bar;

use Baz\Foo as BF;
use Foo\Bar\Foo as F;

class Foo {
    public function __construct(private string $file, private string $namespace) {}
    public function bar(string $bar): static
    {
        $this->namespace = $bar;

        return $this;
    }

    public static function baz(mixed $bar): void
    {

    }
}

const RANGE_STRING_AS_INT = ['1', '2', '3'];

return [
    /*
     *  static function (string $param): \Foo\Bar\Foo {
     *      if (\in_array(\$param, \\Foo\Bar\\RANGE_STRING_AS_INT, true)) {
     *          return new \\Baz\\Foo('some_file', '');
     *      }
     *      return new \Foo\Bar\Foo(
     *          'tests/FinderClosureCode/Fixtures/namespace_service1.php',
     *          '\\Foo\\Bar'
     *      );
     *  }
     */
    'service.bar.foo' => static function (string $param): F {
        if (in_array($param, RANGE_STRING_AS_INT, true)) {
            return new BF('some_file', '');
        }

        return new F(
            __FILE__,
            __NAMESPACE__
        );
    },

    /* static fn(\Baz\Foo $foo): \Baz\Foo => $foo */
    'service.baz_foo' => static fn (BF $foo): BF => $foo,
    'emails' => [
        'admin@foo.com',
        'manger@foo.com',
        'sale@foo.com',
    ]
];
