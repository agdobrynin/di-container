<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Foo
{
    public function __construct(
        public readonly QuxInterface $qux
    ) {}
}
