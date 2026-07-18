<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\AttributeResetterConfig;

use Closure;
use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(
    resetter: static function (Baz $baz): void {
        $resetter = fn () => $this->val = '';

        Closure::bind($resetter, $baz, $baz)();
    }
)]
final class Baz
{
    public function __construct(private string $val = 'Lorem ipsum') {}

    public function getVal(): string
    {
        return $this->val;
    }
}
