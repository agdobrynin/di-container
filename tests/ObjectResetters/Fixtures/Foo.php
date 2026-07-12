<?php

declare(strict_types=1);

namespace Tests\ObjectResetters\Fixtures;

final class Foo
{
    public string $foo;

    public function flush(): void
    {
        $this->foo = 'null';
    }
}

function resetFoo(Foo $foo): void
{
    $foo->foo = 'fn';
}
