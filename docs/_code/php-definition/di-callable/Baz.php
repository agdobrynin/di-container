<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php// [!code highlight]

namespace App\Services;

final class Baz
{
    // …

    public static function factoryMethod(Bar $bar, string $name): string
    {
        return $name .' '. $bar->doSomething();
    }
}
