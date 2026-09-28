<?php
declare(strict_types=1);

// file: /app/src/Services/Rules.php [!code highlight]

namespace App\Services;

use App\Interfaces\RuleInterface;

final class Rules
{
    /** @var RuleInterface[] */
    public readonly array $rules;//[!code highlight]

    public function __construct(
        RuleInterface ...$inputRule//[!code warning]
    ) {
        $this->rules = $inputRule;//[!code highlight]
    }
}
