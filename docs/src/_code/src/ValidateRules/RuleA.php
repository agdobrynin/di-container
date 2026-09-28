<?php
declare(strict_types=1);

// file: /app/src/ValidateRules/RuleA.php [!code highlight]


namespace App\ValidateRules;

use App\Interfaces\RuleInterface;

final class RuleA implements RuleInterface//[!code ++]
{
    public function validate(mixed $input): mixed
    {
        // …
    }
}