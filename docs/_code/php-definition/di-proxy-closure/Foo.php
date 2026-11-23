<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use Closure;

final class Foo
{
    /**
     * @param Closure(): Bar $barProxy
     */
    public function __construct(private readonly Closure $barProxy) {}

    public function getBar(): Bar
    {
        return ($this->barProxy)();
    }
}
