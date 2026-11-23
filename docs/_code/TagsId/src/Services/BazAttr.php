<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag('tags.internal')]// [!code ++]
final class Baz
{
    // …
}
