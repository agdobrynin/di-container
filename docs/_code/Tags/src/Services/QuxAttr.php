<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php //[!code highlight]

namespace App\Service;

use Kaspi\DiContainer\Attributes\Tag;//[!code highlight]

#[Tag('tags.internal_service')]//[!code highlight]
final class Qux
{
    // …
}
