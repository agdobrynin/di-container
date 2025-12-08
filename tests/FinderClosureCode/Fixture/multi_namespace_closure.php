<?php
declare(strict_types=1);

namespace Events {
    use Tests\_var\cache\Qux as Q;
    // static fn(\Events\Foo $a, \Events\Bar $b, \App\Qux $q) => true
    $fn1 = static fn(Foo $a, Bar $b, Q $q) => true;
}

namespace Services {
    // static fn(\Services\Foo $a, \Services\Bar $b, \Services\Baz\Qux $q) => true
    $fn2 = static fn(Foo $a, Bar $b, Baz\Qux $q) => true;
}
namespace App {
// static fn(\App\Foo $a, \App\Bar $b, \App\Baz $q) => true
    $fn3 = static fn(Foo $a, Bar $b, Baz $q) => true;
}

namespace {
    // static fn(\Foo $a, \Bar $b, \Baz $q) => true
    $fn4 = static fn(Foo $a, Bar $b, Baz $q) => true;

    return [
        'fn1' => &$fn1,
        'fn2' => &$fn2,
        'fn3' => &$fn3,
        'fn4' => &$fn4,
    ];
}
