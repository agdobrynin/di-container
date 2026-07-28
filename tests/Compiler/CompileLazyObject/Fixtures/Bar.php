<?php

declare(strict_types=1);

namespace Tests\Compiler\CompileLazyObject\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\SetupImmutable;

#[Autowire(
    setups: [
        'withVal' => new SetupImmutable('Lorem ipsum in Bar'),
    ],
    isLazy: true,
)]
final class Bar
{
    private string $val = '';

    public function __construct(private readonly Baz $baz) {}

    public function withVal(string $val): self
    {
        $this->val = $val;

        return $this;
    }

    public function getVal(): string
    {
        return $this->val;
    }

    public function getBaz(): Baz
    {
        return $this->baz;
    }
}
