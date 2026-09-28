<?php
declare(strict_types=1);

// file: /app/src/Rules/RuleB.php // [!code highlight]

namespace App\Rules;


use Kaspi\DiContainer\Attributes\Tag;// [!code highlight]

#[Tag('tags.other-rulers')]// [!code highlight]
final class RuleB
{
    // …
}
