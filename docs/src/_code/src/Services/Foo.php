<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use App\Enums\SetupEnum;

final class Foo
{
    public function __construct(
        public readonly Bar $bar,
        public readonly SetupEnum $setupType,
        public readonly string $mailTo,
        public readonly bool $debug,
    ) {}
}
