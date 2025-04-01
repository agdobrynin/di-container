<?php
declare(strict_types=1);

namespace Faz\Bar {
    use Baz\Bar\Foo as BazBarFoo;
    class Foo
    {
        public function __construct(private BazBarFoo $foo, private ?string $str = null){}
    }
}

namespace Baz\Bar {
    use Faz\Bar\Foo as OtherFoo;

    class Foo {

        private OtherFoo $otherFoo;

        public function __construct(string $name, array $params = [])
        {
        }

        public function setup(OtherFoo $foo): self
        {
            $this->otherFoo = $foo;

            return $this;
        }
    }
}

namespace {
    class Foo {
        public static function setup(?string $name = null, array $params = []): void
        {}
    }
}
