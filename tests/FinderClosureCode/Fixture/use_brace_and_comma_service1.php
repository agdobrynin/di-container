<?php

use \Foo\{
    Bar, Baz
};
use Bar\Foo as BF, Baz\Qnx;
use \Qnx\{Foo as F, Bar as B};

return [
    'fn1' => static fn(Bar $bar, Baz $baz): array => [$bar, $baz],

    'fn2' => static function (): array {
        return array_map(
            null,
            Foo::array_map(),
            (new Qnx\Foo())->array_map(),
        );
    },

    'fn3' => static function (F $foo): array {
        return $foo->array_map((new B\Fuzz())->toArray());
    },
];
