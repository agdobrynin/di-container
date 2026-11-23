<?php
declare(strict_types=1);

//file: /app/src/Services/Foo.php// [!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(public readonly string $value) {}
}
