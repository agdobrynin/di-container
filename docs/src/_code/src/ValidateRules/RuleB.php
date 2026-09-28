<?php
declare(strict_types=1);

// file: /app/src/ValidateRules/RuleB.php [!code highlight]

namespace App\ValidateRules;

final class RuleB//[!code warning]
{
    public function validate(mixed $input): mixed
    {
        // …
    }
}