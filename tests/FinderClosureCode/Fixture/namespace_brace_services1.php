<?php

declare(strict_types=1);

namespace Services\Foo {

    use ArrayIterator;
    use Closure;
    use Iterator;
    use Services\Baz\Qux;

    class Bar
    {
        public function __construct(private Qux $param) {}
        public function class(Iterator $iterator): Qux
        {
            return $this->param;
        }

        public static function asClosure(): Closure
        {
            return static function (array $args): Bar {
                return new Bar(
                    new Qux(
                        arrayIterator: new ArrayIterator($args)
                    )
                );
            };
        }
    }
}

namespace Services\Baz {

    use ArrayIterator;
    use Iterator;
    use Services\Foo\Bar;

    class Qux
    {
        public function __construct(private ArrayIterator $arrayIterator) {}
        public function iterator(): Iterator
        {
            return $this->arrayIterator;
        }
    }

    return [
        'service.crazy_bar' => static function (Bar $bar, ArrayIterator $arrayIterator): Qux {
            $qux = new Qux(arrayIterator: $arrayIterator);

            return $bar->class(iterator: $qux->iterator());
        },
        'service.closure_from_class' => Bar::asClosure(),
    ];
}
