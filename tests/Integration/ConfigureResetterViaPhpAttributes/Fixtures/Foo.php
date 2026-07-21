<?php

declare(strict_types=1);

namespace Tests\Integration\ConfigureResetterViaPhpAttributes\Fixtures;

use Closure;
use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(
    isSingleton: true,
    resetter: static function (Foo $bar): void {
        $resetter = function (): void {
            $this->val = '';
        };

        Closure::bind($resetter, $bar, $bar)();
    }
)]
final class Foo
{
    public function __construct(private string $val = 'Lorem ipsum foo') {}

    public function getVal(): string
    {
        return $this->val;
    }
}
