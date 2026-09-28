<?php
declare(strict_types=1);

// file: /app/src/Rules/RuleA.php // [!code highlight]

namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;//[!code ++]

#[Tag('tags.lite-rules')]//[!code ++]
final class RuleA
{
    // …
}
