<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code highlight]

#[Tag('tags.ordered')]// [!code highlight]
final class Bar
{
    // …
}
