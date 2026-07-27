<?php

declare(strict_types=1);

namespace Tests\Compiler\CompileLazyObject\Fixtures;

final class Foo
{
    public function __construct(
        private readonly Bar $bar,
        private readonly Qux $qux,
    ) {}

    public function getBar(): Bar
    {
        return $this->bar;
    }

    public function getQux(): Qux
    {
        return $this->qux;
    }
}
