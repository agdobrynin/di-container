<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code highlight]

#[Tag(// [!code highlight]
    'tags.ordered',// [!code highlight]
    priority: 10,// [!code ++]
)]// [!code highlight]
final class Baz
{
    // …
}
