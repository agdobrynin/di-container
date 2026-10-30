<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php //[!code highlight]

namespace App\Service;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(//[!code highlight]
    'tags.ordered',//[!code highlight]
    priorityMethod: 'getPriority',//[!code ++]
)]//[!code highlight]
final class Qux
{
    // …
    public static function getPriority(): int//[!code ++]
    {
        return 100;//[!code ++]
    }//[!code ++]
}
