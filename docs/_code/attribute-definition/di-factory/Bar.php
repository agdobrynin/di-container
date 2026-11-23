<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php // [!code highlight]

namespace App\Services;

final class Bar
{
    public function __construct(
        public readonly Baz $baz
    ) {}

    public function calculateSomething(): string
    {
        if (0 < $this->baz->calculateSomething()) {
            return 'Fuuz';
        }

        return 'Ok';
    }
}
