<?php
declare(strict_types=1);

//file: /app/src/Services/Bar.php// [!code highlight]

namespace App\Services;

final class Bar
{
    // …

    public function doSomething(): string
    {
        return 'dolor sit amet';
    }
}
