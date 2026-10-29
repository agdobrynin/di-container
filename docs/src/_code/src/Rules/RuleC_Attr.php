<?php
declare(strict_types=1);

// file: /app/src/Rules/RuleC.php // [!code highlight]

namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;//[!code ++]

#[Tag(//[!code ++]
    'tags.lite-rules',//[!code ++]
    priority: 100//[!code ++]
)]//[!code ++]
final class RuleC
{
    // …
}
