<?php
declare(strict_types=1);

// file: /app/src/ValidateRules/RuleC.php [!code highlight]

namespace App\ValidateRules;

use App\Interfaces\RuleInterface;

final class RuleC implements RuleInterface//[!code ++]
{
    public function validate(mixed $input): mixed
    {
        // …
    }
}